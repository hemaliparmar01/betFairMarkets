<?php

namespace App\Console\Commands;

use Amp\Websocket\Client\WebsocketHandshake;
use App\Events\AmericanFootballSportsScoreUpdated;
use App\Events\BaseballSportsScoreUpdated;
use App\Events\BasketballSportsScoreUpdated;
use App\Events\CricketSportsScoreUpdated;
use App\Events\FootballSportsScoreUpdated;
use App\Events\GreyhoundsSportsScoreUpdated;
use App\Events\HorseRacingSportsScoreUpdated;
use App\Events\IceHockeySportsScoreUpdated;
use App\Events\TennisSportsScoreUpdated;
use App\Http\Controllers\Users\AmericanFootball\AmericanFootballSyncController;
use App\Http\Controllers\Users\Baseball\BaseballSyncController;
use App\Http\Controllers\Users\Basketball\BasketballSyncController;
use App\Http\Controllers\Users\Cricket\CricketSyncController;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Http\Controllers\Users\Greyhounds\GreyhoundsSyncController;
use App\Http\Controllers\Users\HorseRacing\HorseRacingSyncController;
use App\Http\Controllers\Users\IceHockey\IceHockeySyncController;
use App\Http\Controllers\Users\Tennis\TennisSyncController;
use App\Models\FootballMarket;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;
use function Amp\Websocket\Client\connect;

class ListenBetfairOdds extends Command
{
    protected $signature = 'betfair:listen';

    protected $aliases = ['football:odds-listen'];

    protected $description = 'Listen to Betfair odds updates for all supported sports';

    private float $lastHealthHeartbeat = 0;

    public function handle(): int
    {
        ini_set('memory_limit', '-1');

        $listenerLock = Cache::store('redis')->lock('Football:betfair-odds-listener', 120);
        if (! $listenerLock->get()) {
            $this->warn('Betfair Odds listener is already running.');

            return self::SUCCESS;
        }

        EventLoop::repeat(60, function () use ($listenerLock): void {
            if (! $listenerLock->refresh(120)) {
                Log::critical('Betfair Odds listener lost its singleton lock and is stopping.');
                exit(self::FAILURE);
            }
        });
        $socket = null;
        register_shutdown_function(static function () use ($listenerLock, &$socket): void {
            try {
                $socket?->close();
            } catch (Throwable $e) {
                Log::warning('Betfair Odds WebSocket shutdown close failed.', [
                    'message' => $e->getMessage(),
                ]);
            } finally {
                Cache::store('redis')->put('Betfair:racing:connection', ['state' => 'disconnected', 'at' => time()], 86400);
                $listenerLock->release();
            }
        });

        $stopListener = static function (): void {
            exit(self::SUCCESS);
        };
        EventLoop::onSignal(SIGINT, $stopListener);
        EventLoop::onSignal(SIGTERM, $stopListener);

        $key = GetBetfairKey();

        if (! $key) {
            $this->error('BETFAIR_API_KEY is missing.');

            return self::FAILURE;
        }

        $backoff = 1;
        $books = ['live' => [], 'prematch' => [], 'greyhounds' => [], 'horse_prematch' => []];
        $pendingChanges = ['live' => [], 'prematch' => [], 'greyhounds' => [], 'horse_prematch' => []];

        while (true) {
            $socket = null;
            $flushTimer = null;
            $nextFlushAt = 0.0;
            $connectedAt = null;

            try {
                foreach (['greyhounds', 'horse_prematch'] as $racingFeed) {
                    Cache::store('redis')->put('Betfair:racing:feed:'.$racingFeed, ['state' => 'waiting', 'at' => null], 86400);
                }
                $socket = connect(new WebsocketHandshake('wss://betfair-odds.com/betfair/v1/stream'));
                $connectedAt = microtime(true);
                $books = ['live' => [], 'prematch' => [], 'greyhounds' => [], 'horse_prematch' => []];
                $snapshotStarted = ['live' => false, 'prematch' => false, 'greyhounds' => false, 'horse_prematch' => false];
                $snapshotChanges = ['live' => [], 'prematch' => [], 'greyhounds' => [], 'horse_prematch' => []];

                $socket->sendText(json_encode([
                    'action' => 'authenticate',
                    'apiKey' => $key,
                ], JSON_THROW_ON_ERROR));

                $this->info('Connected to Betfair Odds WebSocket; authenticating.');

                foreach ($socket as $message) {
                    $frame = json_decode($message->buffer(), true);

                    if (! is_array($frame)) {
                        continue;
                    }

                    $this->recordRacingFeedHealth($frame);

                    $type = $frame['type'] ?? null;

                    if ($type === 'authenticated') {
                        if (($frame['ws_addon'] ?? false) !== true) {
                            throw new \RuntimeException('Betfair WebSocket add-on is inactive.');
                        }

                        $socket->sendText(json_encode([
                            'action' => 'subscribe',
                            'feeds' => ['live', 'prematch', 'greyhounds', 'horse_prematch'],
                            'sports' => ['soccer', 'cricket', 'ice hockey', 'american football', 'baseball', 'basketball', 'tennis', 'greyhounds', 'horse_racing'],
                            'mode' => 'delta',
                        ], JSON_THROW_ON_ERROR));

                        $this->info('Authenticated; subscribed to live, prematch, greyhounds and horse racing feeds in delta mode.');

                        continue;
                    }

                    if ($type === 'ping') {
                        $socket->sendText(json_encode(['action' => 'pong'], JSON_THROW_ON_ERROR));

                        continue;
                    }

                    if ($type === 'error') {
                        $isDuplicateConnection = ($frame['code'] ?? null) === 'too_many_connections';
                        Log::log($isDuplicateConnection ? 'warning' : 'error', 'Betfair Odds WebSocket error', [
                            'code' => $frame['code'] ?? null,
                            'message' => $frame['message'] ?? null,
                            'feed' => $frame['feed'] ?? null,
                        ]);
                        $this->error('Betfair WebSocket error: '.($frame['code'] ?? 'unknown'));

                        if ($isDuplicateConnection) {
                            throw new \RuntimeException('Betfair API rejected a duplicate WebSocket connection.');
                        }

                        continue;
                    }

                    if ($type === 'stale') {
                        Log::warning('Betfair Odds feed staleness changed', [
                            'feed' => $frame['feed'] ?? null,
                            'stale' => $frame['stale'] ?? null,
                        ]);

                        continue;
                    }

                    if ($type === 'subscribed') {
                        $this->info('Subscribed to '.($frame['feed'] ?? 'unknown').' odds feed.');

                        continue;
                    }

                    $feed = $frame['feed'] ?? null;
                    if (! isset($books[$feed])) {
                        continue;
                    }
                    $changedEvents = [];

                    if ($type === 'snapshot') {
                        if (! $snapshotStarted[$feed]) {
                            $books[$feed] = [];
                            $snapshotChanges[$feed] = [];
                            $snapshotStarted[$feed] = true;
                        }

                        foreach (($frame['liveMap'] ?? []) as $eventId => $event) {
                            if (! is_array($event)) {
                                continue;
                            }

                            $books[$feed][(string) $eventId] = $event;
                            $snapshotChanges[$feed][(string) $eventId] = $event;
                        }

                        if (array_key_exists('final', $frame) && $frame['final'] !== true) {
                            continue;
                        }

                        $changedEvents = $snapshotChanges[$feed];
                        $snapshotChanges[$feed] = [];
                        if (in_array($feed, ['greyhounds', 'horse_prematch'], true) && ($frame['meta']['stale'] ?? false) === false) {
                            foreach (getInRedis($feed === 'greyhounds' ? 'Greyhounds:matches' : 'HorseRacing:matches') ?? [] as $race) {
                                if (($race['result_status'] ?? '') !== 'Finished' && ! isset($books[$feed][(string) $race['betfair_match_id']])) {
                                    $changedEvents[(string) $race['betfair_match_id']] = ['removed' => true];
                                }
                            }
                        }
                    } elseif ($type === 'update') {
                        foreach (($frame['changed'] ?? []) as $eventId => $event) {
                            if (! is_array($event)) {
                                continue;
                            }

                            $books[$feed][(string) $eventId] = $event;
                            $changedEvents[(string) $eventId] = $event;
                        }
                    } elseif ($type === 'delta') {
                        foreach (($frame['events'] ?? []) as $eventId => $eventDelta) {
                            if (! is_array($eventDelta)) {
                                continue;
                            }

                            $eventId = (string) $eventId;
                            $event = ($eventDelta['new'] ?? false) === true
                                ? ($eventDelta['head'] ?? [])
                                : ($books[$feed][$eventId] ?? []);

                            if (isset($eventDelta['head']) && is_array($eventDelta['head'])) {
                                $event = array_replace($event, $eventDelta['head']);
                            }

                            $rows = [];
                            foreach (($event['markets'] ?? []) as $row) {
                                if (is_array($row)) {
                                    $rows[$this->rowKey($row)] = $row;
                                }
                            }
                            foreach (($eventDelta['rows'] ?? []) as $row) {
                                if (is_array($row)) {
                                    $rows[$this->rowKey($row)] = $row;
                                }
                            }
                            foreach (($eventDelta['removedRows'] ?? []) as $removedRow) {
                                unset($rows[(string) $removedRow]);
                            }

                            $event['markets'] = array_values($rows);
                            if (in_array($feed, ['greyhounds', 'horse_prematch'], true) && ($eventDelta['status'] ?? null) !== null) {
                                $event['status'] = $eventDelta['status'];
                                if (array_any($eventDelta['rows'] ?? [], static fn (array $row): bool => (string) ($row['marketId'] ?? '') === $eventId)
                                    || array_any($eventDelta['removedRows'] ?? [], static fn (string $row): bool => str_starts_with($row, $eventId.' '))) {
                                    $event['win_market_status'] = $eventDelta['status'];
                                }
                            }
                            $books[$feed][$eventId] = $event;
                            $changedEvents[$eventId] = $event;
                        }
                    } else {
                        continue;
                    }

                    foreach (($frame['removed'] ?? []) as $removedEventId) {
                        unset($books[$feed][(string) $removedEventId]);
                        if (in_array($feed, ['greyhounds', 'horse_prematch'], true)) {
                            $changedEvents[(string) $removedEventId] = ['removed' => true];
                        }
                    }

                    if ($changedEvents === []) {
                        continue;
                    }

                    foreach ($changedEvents as $eventId => $event) {
                        if (in_array($feed, ['greyhounds', 'horse_prematch'], true) && isset($pendingChanges[$feed][$eventId])
                            && (($event['removed'] ?? false) || in_array($event['win_market_status'] ?? $event['status'] ?? '', ['SUSPENDED', 'CLOSED'], true))) {
                            try {
                                if ($this->flushPendingChanges($pendingChanges)) {
                                    Event::dispatch(new FootballSportsScoreUpdated);
                                }
                            } catch (Throwable $e) {
                                $pendingChanges[$feed][$eventId]['_next_event'] = $event;
                                Log::warning('Betfair race status deferred until pending odds are recorded', ['exception' => $e::class]);

                                continue;
                            }
                        }
                        $pendingChanges[$feed][$eventId] = $this->bufferOddsEvent($pendingChanges[$feed][$eventId] ?? [], $event, microtime(true));
                    }

                    if ($flushTimer === null) {
                        $flushTimer = EventLoop::repeat(0.25, function () use (&$flushTimer, &$pendingChanges, &$nextFlushAt): void {
                            if (microtime(true) < $nextFlushAt) {
                                return;
                            }

                            try {
                                if ($this->flushPendingChanges($pendingChanges)) {
                                    Event::dispatch(new FootballSportsScoreUpdated);
                                }
                            } catch (Throwable $e) {
                                Log::error('Betfair Odds pending update processing failed; the WebSocket remains connected.', [
                                    'exception' => $e::class,
                                    'message' => $e->getMessage(),
                                ]);
                            }

                            if ($pendingChanges['live'] === [] && $pendingChanges['prematch'] === [] && ($pendingChanges['greyhounds'] ?? []) === [] && ($pendingChanges['horse_prematch'] ?? []) === []) {
                                EventLoop::cancel($flushTimer);
                                $flushTimer = null;
                            } else {
                                $nextFlushAt = microtime(true) + 2;
                            }
                        });
                    }
                }
            } catch (Throwable $e) {
                $isDuplicateConnection = str_contains($e->getMessage(), 'duplicate WebSocket connection');
                $isAddonInactive = $e->getMessage() === 'Betfair WebSocket add-on is inactive.';
                if ($isAddonInactive) {
                    $backoff = 300;
                } elseif ($connectedAt !== null && microtime(true) - $connectedAt >= 60) {
                    $backoff = 1;
                }
                Log::log($isDuplicateConnection || $isAddonInactive ? 'warning' : 'error', 'Betfair Odds WebSocket disconnected', [
                    'message' => $e->getMessage(),
                    'retry_in_seconds' => $backoff,
                ]);
                $this->error('Betfair Odds WebSocket disconnected: '.$e->getMessage());
            } finally {
                Cache::store('redis')->put('Betfair:racing:connection', ['state' => 'disconnected', 'at' => time()], 86400);
                if ($flushTimer !== null) {
                    EventLoop::cancel($flushTimer);
                    $flushTimer = null;
                }

                try {
                    $socket?->close();
                } catch (Throwable $e) {
                    Log::warning('Betfair Odds WebSocket close before reconnect failed.', [
                        'message' => $e->getMessage(),
                    ]);
                } finally {
                    $socket = null;
                }

                try {
                    if ($this->flushPendingChanges($pendingChanges)) {
                        Event::dispatch(new FootballSportsScoreUpdated);
                    }
                } catch (Throwable $e) {
                    Log::error('Betfair Odds pending update processing during reconnect failed.', [
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            delay($backoff);
            $backoff = min($backoff * 2, 30);
        }
    }

    /** @param array<string, mixed> $frame */
    private function recordRacingFeedHealth(array $frame): void
    {
        if (microtime(true) - $this->lastHealthHeartbeat >= 5) {
            Cache::store('redis')->put('Betfair:racing:connection', ['state' => 'connected', 'at' => time()], 86400);
            $this->lastHealthHeartbeat = microtime(true);
        }
        $feed = $frame['feed'] ?? null;
        if (! in_array($feed, ['greyhounds', 'horse_prematch'], true)) {
            return;
        }
        $type = $frame['type'] ?? '';
        if ($type === 'stale') {
            $state = ($frame['stale'] ?? true) ? 'stale' : 'live';
        } elseif ($type === 'error') {
            $state = 'unavailable';
        } elseif (in_array($type, ['snapshot', 'delta', 'update'], true)) {
            $state = ($frame['meta']['stale'] ?? false) ? 'stale' : 'live';
        } else {
            return;
        }
        Cache::store('redis')->put('Betfair:racing:feed:'.$feed, ['state' => $state, 'at' => time()], 86400);
    }

    private function flushPendingChanges(array &$pendingChanges): bool
    {
        $updated = false;

        foreach (['live' => true, 'prematch' => false, 'greyhounds' => false, 'horse_prematch' => false] as $feed => $isLive) {
            if (($pendingChanges[$feed] ?? []) === []) {
                continue;
            }

            $events = $pendingChanges[$feed];
            $followingEvents = [];
            foreach ($events as $eventId => &$event) {
                if (isset($event['_next_event'])) {
                    $followingEvents[$eventId] = $event['_next_event'];
                }
                unset($event['_next_event']);
                foreach ($event['markets'] ?? [] as $index => $runner) {
                    $updates = $event['_odds_updates'][$this->rowKey($runner)] ?? [];
                    if ($updates !== []) {
                        $event['markets'][$index]['_odds_updates'] = $updates;
                    }
                }
                unset($event['_odds_updates']);
            }
            unset($event);
            try {
                if ($feed === 'greyhounds') {
                    if (app(GreyhoundsSyncController::class)->mergeBetfairWebSocketData($events)) {
                        try {
                            Event::dispatch(new GreyhoundsSportsScoreUpdated);
                        } catch (Throwable $e) {
                            Log::warning('Greyhounds odds refresh broadcast failed', ['exception' => $e::class]);
                        }
                    }
                    $pendingChanges[$feed] = $followingEvents;

                    continue;
                }
                if ($feed === 'horse_prematch') {
                    if (app(HorseRacingSyncController::class)->mergeBetfairWebSocketData($events)) {
                        try {
                            Event::dispatch(new HorseRacingSportsScoreUpdated);
                        } catch (Throwable $e) {
                            Log::warning('HorseRacing odds refresh broadcast failed', ['exception' => $e::class]);
                        }
                    }
                    $pendingChanges[$feed] = $followingEvents;

                    continue;
                }
                $footballEvents = array_filter($events, static fn (array $event): bool => strtolower((string) ($event['sport'] ?? 'soccer')) === 'soccer');
                $cricketEvents = array_filter($events, static fn (array $event): bool => strtolower((string) ($event['sport'] ?? '')) === 'cricket');
                $iceHockeyEvents = array_filter($events, static fn (array $event): bool => strtolower((string) ($event['sport'] ?? '')) === 'ice hockey');
                $americanFootballEvents = array_filter($events, static fn (array $event): bool => strtolower((string) ($event['sport'] ?? '')) === 'american football');
                $baseballEvents = array_filter($events, static fn (array $event): bool => strtolower((string) ($event['sport'] ?? '')) === 'baseball');
                $basketballEvents = array_filter($events, static fn (array $event): bool => strtolower((string) ($event['sport'] ?? '')) === 'basketball');
                $tennisEvents = array_filter($events, static fn (array $event): bool => strtolower((string) ($event['sport'] ?? '')) === 'tennis');
                $feedUpdated = $footballEvents !== [] && withRedisLock(
                    'Football:matches',
                    fn (): bool => $this->updateFootballCache($footballEvents, $isLive)
                );
                if ($cricketEvents !== [] && app(CricketSyncController::class)->mergeBetfairWebSocketData($cricketEvents, $isLive)) {
                    try {
                        Event::dispatch(new CricketSportsScoreUpdated);
                    } catch (Throwable $e) {
                        Log::warning('Cricket odds refresh broadcast failed', ['exception' => $e::class]);
                    }
                }
                if ($iceHockeyEvents !== [] && app(IceHockeySyncController::class)->mergeBetfairWebSocketData($iceHockeyEvents, $isLive)) {
                    try {
                        Event::dispatch(new IceHockeySportsScoreUpdated);
                    } catch (Throwable $e) {
                        Log::warning('Ice Hockey odds refresh broadcast failed', ['exception' => $e::class]);
                    }
                }
                if ($americanFootballEvents !== [] && app(AmericanFootballSyncController::class)->mergeBetfairWebSocketData($americanFootballEvents, $isLive)) {
                    try {
                        Event::dispatch(new AmericanFootballSportsScoreUpdated);
                    } catch (Throwable $e) {
                        Log::warning('American Football odds refresh broadcast failed', ['exception' => $e::class]);
                    }
                }
                if ($baseballEvents !== [] && app(BaseballSyncController::class)->mergeBetfairWebSocketData($baseballEvents, $isLive)) {
                    try {
                        Event::dispatch(new BaseballSportsScoreUpdated);
                    } catch (Throwable $e) {
                        Log::warning('Baseball odds refresh broadcast failed', ['exception' => $e::class]);
                    }
                }
                if ($basketballEvents !== [] && app(BasketballSyncController::class)->mergeBetfairWebSocketData($basketballEvents, $isLive)) {
                    try {
                        Event::dispatch(new BasketballSportsScoreUpdated);
                    } catch (Throwable $e) {
                        Log::warning('Basketball odds refresh broadcast failed', ['exception' => $e::class]);
                    }
                }
                if ($tennisEvents !== [] && app(TennisSyncController::class)->mergeBetfairWebSocketData($tennisEvents, $isLive)) {
                    try {
                        Event::dispatch(new TennisSportsScoreUpdated);
                    } catch (Throwable $e) {
                        Log::warning('Tennis odds refresh broadcast failed', ['exception' => $e::class]);
                    }
                }
            } catch (LockTimeoutException $e) {
                Log::warning('Betfair Odds cache write lock timed out; pending odds will be retried without reconnecting.', [
                    'feed' => $feed,
                    'pending_events' => count($pendingChanges[$feed]),
                ]);

                continue;
            }

            $pendingChanges[$feed] = $followingEvents;
            $updated = $feedUpdated || $updated;
        }

        return $updated;
    }

    /**
     * Keep every received quote while cache writes remain batched.
     *
     * @param  array<string, mixed>  $pending
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function bufferOddsEvent(array $pending, array $event, float $receivedAt): array
    {
        $updates = $pending['_odds_updates'] ?? [];
        foreach ($event['markets'] ?? [] as $runner) {
            $key = $this->rowKey($runner);
            $quote = array_intersect_key($runner, array_flip(['backOdds', 'backSize', 'layOdds', 'laySize']));
            $points = $updates[$key] ?? [];
            $last = $points !== [] ? $points[array_key_last($points)] : null;
            unset($last['observed_at']);
            if ($last !== $quote) {
                $quote['observed_at'] = $receivedAt;
                $updates[$key][] = $quote;
            }
        }
        $event['_odds_updates'] = $updates;

        return $event;
    }

    private function rowKey(array $row): string
    {
        $handicap = (float) ($row['handicap'] ?? 0);
        $normalizedHandicap = rtrim(rtrim(number_format($handicap, 10, '.', ''), '0'), '.');

        return ($row['marketId'] ?? '').' '.($row['runnerId'] ?? '').' '.($normalizedHandicap === '-0' || $normalizedHandicap === '' ? '0' : $normalizedHandicap);
    }

    private function updateFootballCache(array $events, bool $isLive): bool
    {
        $matches = getInRedis('Football:matches') ?? [];
        if ($matches === []) {
            return false;
        }

        $matchKeys = [];
        foreach ($matches as $matchKey => $match) {
            if (
                ($match['data_sync_complete'] ?? false) === true
                && ($match['result_status'] ?? null) !== 'Finished'
                && isset($match['betfair_match_id'])
            ) {
                $matchKeys[(string) $match['betfair_match_id']] = $matchKey;
            }
        }

        $supportedMarkets = [
            'MATCH_ODDS', 'OVER_UNDER_05', 'OVER_UNDER_15', 'OVER_UNDER_25', 'OVER_UNDER_35',
            'OVER_UNDER_45', 'OVER_UNDER_55', 'OVER_UNDER_65', 'OVER_UNDER_75', 'OVER_UNDER_85',
            'BOTH_TEAMS_TO_SCORE', 'CORRECT_SCORE', 'HALF_TIME', 'HALF_TIME_FULL_TIME',
            'DRAW_NO_BET', 'HALF_TIME_SCORE',
        ];
        $updated = false;
        $now = time();
        $changedMatches = [];
        $databaseMarkets = [];
        $missingMarketMatchKeys = [];

        foreach (array_keys($events) as $eventId) {
            $matchKey = $matchKeys[(string) $eventId] ?? null;
            if ($matchKey !== null && empty($matches[$matchKey]['markets'])) {
                $missingMarketMatchKeys[$matchKey] = true;
            }
        }

        if ($missingMarketMatchKeys !== []) {
            $databaseRows = FootballMarket::query()
                ->whereIn('football_match_unique_key', array_keys($missingMarketMatchKeys))
                ->get(['football_match_unique_key', 'payload']);

            foreach ($databaseRows as $databaseRow) {
                $payload = $databaseRow->payload;
                if (is_array($payload)) {
                    $databaseMarkets[$databaseRow->football_match_unique_key][] = $payload;
                }
            }
        }

        foreach ($events as $eventId => $event) {
            $matchKey = $matchKeys[(string) $eventId] ?? null;
            if (
                $matchKey === null
                || ($matches[$matchKey]['result_status'] ?? null) === 'Finished'
                || ! is_array($event['markets'] ?? null)
            ) {
                continue;
            }

            $previousMarkets = $matches[$matchKey]['markets'] ?? [];
            $storedMarkets = $databaseMarkets[$matchKey] ?? [];
            $marketLiquidity = [];

            foreach ($event['markets'] as $runner) {
                if (! in_array($runner['marketType'] ?? null, $supportedMarkets, true)) {
                    continue;
                }

                $marketKey = ($runner['marketId'] ?? '').'_'.($runner['marketType'] ?? '');
                $marketLiquidity[$marketKey] = ($marketLiquidity[$marketKey] ?? 0)
                    + (float) ($runner['backSize'] ?? 0)
                    + (float) ($runner['laySize'] ?? 0);
            }

            $updatedMarkets = [];
            foreach ($event['markets'] as $runner) {
                if (! in_array($runner['marketType'] ?? null, $supportedMarkets, true)) {
                    continue;
                }

                $marketKey = ($runner['marketId'] ?? '').'_'.($runner['marketType'] ?? '');
                $sortPriority = implode('|', [$runner['runnerId'] ?? $runner['runner'] ?? '', $runner['handicap'] ?? 0]);
                $previousRunner = $previousMarkets[$marketKey][$sortPriority] ?? [];

                if ($previousRunner === []) {
                    foreach ($previousMarkets as $previousMarketRunners) {
                        foreach (($previousMarketRunners ?? []) as $candidate) {
                            $sameRunner = (
                                isset($runner['runnerId'], $candidate['runnerId'])
                                && (string) $candidate['runnerId'] === (string) $runner['runnerId']
                            ) || strcasecmp((string) ($candidate['runner'] ?? ''), (string) ($runner['runner'] ?? '')) === 0;

                            if (
                                ($candidate['marketId'] ?? null) === ($runner['marketId'] ?? null)
                                && ($candidate['marketType'] ?? null) === ($runner['marketType'] ?? null)
                                && $sameRunner
                                && (float) ($candidate['handicap'] ?? 0) === (float) ($runner['handicap'] ?? 0)
                            ) {
                                $previousRunner = $candidate;
                                break 2;
                            }
                        }
                    }
                }

                if ($previousRunner === []) {
                    foreach ($storedMarkets as $candidate) {
                        $sameRunner = (
                            isset($runner['runnerId'], $candidate['runnerId'])
                            && (string) $candidate['runnerId'] === (string) $runner['runnerId']
                        ) || strcasecmp((string) ($candidate['runner'] ?? ''), (string) ($runner['runner'] ?? '')) === 0;

                        if (
                            ($candidate['marketId'] ?? null) === ($runner['marketId'] ?? null)
                                && ($candidate['marketType'] ?? null) === ($runner['marketType'] ?? null)
                            && $sameRunner
                            && (float) ($candidate['handicap'] ?? 0) === (float) ($runner['handicap'] ?? 0)
                        ) {
                            $previousRunner = $candidate;
                            break;
                        }
                    }
                }

                $previousRunner = prepareOddsMonitoring($previousRunner);
                $runner = updateOddsMonitoring($runner, $previousRunner, $now);
                $previousOdds = $runner['previousBackOdds'];
                $currentOdds = $runner['currentBackOdds'];
                $initialOdds = (float) ($runner['initialBackOdds'] ?? 0);

                if (! $isLive && $currentOdds > 0 && $initialOdds > 1) {
                    $baselineOdds = (float) ($previousRunner['prematch_drop_baseline_odds'] ?? 0);
                    if ($baselineOdds <= 0 && ($marketLiquidity[$marketKey] ?? 0) >= 1000) {
                        $baselineOdds = $initialOdds;
                    }

                    $runner['prematch_drop_baseline_odds'] = $baselineOdds ?: null;
                    $runner['prematch_drop_triggered'] = (bool) ($previousRunner['prematch_drop_triggered'] ?? false);
                    $runner['prematch_drop_detected_at'] = $previousRunner['prematch_drop_detected_at'] ?? null;

                    if ($baselineOdds > 0 && ! $runner['prematch_drop_triggered']) {
                        $previousDrop = (($baselineOdds - $previousOdds) / $baselineOdds) * 100;
                        $currentDrop = (($baselineOdds - $currentOdds) / $baselineOdds) * 100;
                        if ($previousDrop < 1 && $currentDrop >= 1) {
                            $runner['prematch_drop_triggered'] = true;
                            $runner['prematch_drop_detected_at'] = $now;
                        }
                    }

                    if (in_array($runner['marketType'], ['MATCH_ODDS', 'OVER_UNDER_15', 'OVER_UNDER_25', 'BOTH_TEAMS_TO_SCORE'], true)) {
                        $snapshots = array_values(array_filter(
                            $previousRunner['market_shock_snapshots'] ?? [],
                            static fn (array $snapshot): bool => (int) ($snapshot['timestamp'] ?? 0) >= ($now - 180)
                        ));
                        $shockDetectedAt = (int) ($previousRunner['market_shock_detected_at'] ?? 0);

                        if ($snapshots === []) {
                            $snapshots[] = ['timestamp' => $now, 'odds' => $previousOdds];
                        }

                        if ($previousOdds !== $currentOdds) {
                            $comparisonOdds = max(array_column($snapshots, 'odds'));
                            $requiredDrop = $comparisonOdds >= 1.20 && $comparisonOdds <= 1.99
                                ? 5
                                : ($comparisonOdds >= 2.00 && $comparisonOdds <= 3.99
                                    ? 8
                                    : ($comparisonOdds >= 4.00 && $comparisonOdds <= 20.00 ? 12 : null));
                            $shockDrop = $comparisonOdds > 0
                                ? (($comparisonOdds - $currentOdds) / $comparisonOdds) * 100
                                : 0;

                            if ($requiredDrop !== null && $shockDrop >= $requiredDrop && $shockDetectedAt < ($now - 1200)) {
                                $shockDetectedAt = $now;
                            }

                            $snapshots[] = ['timestamp' => $now, 'odds' => $currentOdds];
                        }

                        $runner['market_shock_snapshots'] = array_slice($snapshots, -7);
                        $runner['market_shock_detected_at'] = $shockDetectedAt ?: null;
                    }
                }

                $updatedMarkets[$marketKey][$sortPriority] = $runner;
            }

            if ($updatedMarkets === []) {
                continue;
            }

            $matches[$matchKey]['markets'] = $updatedMarkets;

            if (! $isLive) {
                $favoriteOdds = null;
                $favoriteRunner = null;
                $favoriteSide = null;

                foreach ($updatedMarkets as $marketRunners) {
                    foreach ($marketRunners as $runner) {
                        $runnerOdds = (float) ($runner['currentBackOdds'] ?? 0);
                        if (($runner['marketType'] ?? null) !== 'MATCH_ODDS' || $runnerOdds <= 0) {
                            continue;
                        }

                        if (strcasecmp((string) ($runner['runner'] ?? ''), (string) ($matches[$matchKey]['team1'] ?? '')) === 0 && ($favoriteOdds === null || $runnerOdds < $favoriteOdds)) {
                            $favoriteOdds = $runnerOdds;
                            $favoriteRunner = $runner['runner'];
                            $favoriteSide = 'home';
                        }

                        if (strcasecmp((string) ($runner['runner'] ?? ''), (string) ($matches[$matchKey]['team2'] ?? '')) === 0 && ($favoriteOdds === null || $runnerOdds < $favoriteOdds)) {
                            $favoriteOdds = $runnerOdds;
                            $favoriteRunner = $runner['runner'];
                            $favoriteSide = 'away';
                        }
                    }
                }

                $matches[$matchKey]['prematch_favorite_runner'] = $favoriteRunner;
                $matches[$matchKey]['prematch_favorite_odds'] = $favoriteOdds;
                $matches[$matchKey]['prematch_favorite_side'] = $favoriteSide;
            }

            $updated = true;
            $changedMatches[$matchKey] = $matches[$matchKey];
        }

        if ($updated) {
            app(FootballSyncController::class)->persistOddsSnapshots($changedMatches);
            storeInRedis('Football:matches', $matches, 30, false);
        }

        return $updated;
    }
}
