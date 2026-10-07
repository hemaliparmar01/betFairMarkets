<?php

namespace App\Console\Commands;

use Amp\Websocket\Client\WebsocketHandshake;
use App\Events\FootballSportsScoreUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;
use function Amp\Websocket\Client\connect;

class ListenFootballBetfairOdds extends Command
{
    protected $signature = 'football:odds-listen';

    protected $description = 'Listen to Betfair football odds updates';

    public function handle(): int
    {
        $listenerLock = Cache::store('redis')->lock('Football:betfair-odds-listener', 120);
        // if (! $listenerLock->get()) {
        //     $this->warn('Betfair Odds listener is already running.');

        //     return self::SUCCESS;
        // }

        EventLoop::repeat(60, function () use ($listenerLock): void {
            if (! $listenerLock->refresh(120)) {
                Log::critical('Betfair Odds listener lost its singleton lock and is stopping.');
                exit(self::FAILURE);
            }
        });
        register_shutdown_function(static fn () => $listenerLock->release());

        $key = GetBetfairKey();

        if (! $key) {
            $this->error('BETFAIR_API_KEY is missing.');

            return self::FAILURE;
        }

        $backoff = 1;
        $books = ['live' => [], 'prematch' => []];

        while (true) {
            $flushTimer = null;
            $pendingChanges = ['live' => [], 'prematch' => []];

            try {
                $socket = connect(new WebsocketHandshake('wss://betfair-odds.com/betfair/v1/stream'));
                $books = ['live' => [], 'prematch' => []];
                $snapshotStarted = ['live' => false, 'prematch' => false];
                $snapshotChanges = ['live' => [], 'prematch' => []];

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

                    $type = $frame['type'] ?? null;

                    if ($type === 'authenticated') {
                        $backoff = 1;
                        if (($frame['ws_addon'] ?? false) !== true) {
                            throw new \RuntimeException('Betfair WebSocket add-on is inactive.');
                        }

                        $socket->sendText(json_encode([
                            'action' => 'subscribe',
                            'feeds' => ['live', 'prematch'],
                            'sports' => ['soccer'],
                            'mode' => 'delta',
                        ], JSON_THROW_ON_ERROR));

                        $this->info('Authenticated; subscribed to live and prematch soccer odds in delta mode.');

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
                            $books[$feed][$eventId] = $event;
                            $changedEvents[$eventId] = $event;
                        }
                    } else {
                        continue;
                    }

                    foreach (($frame['removed'] ?? []) as $removedEventId) {
                        unset($books[$feed][(string) $removedEventId]);
                    }

                    if ($changedEvents === []) {
                        continue;
                    }

                    foreach ($changedEvents as $eventId => $event) {
                        $pendingChanges[$feed][$eventId] = $event;
                    }

                    if ($flushTimer === null) {
                        $flushTimer = EventLoop::delay(0.25, function () use (&$flushTimer, &$pendingChanges): void {
                            if ($this->flushPendingChanges($pendingChanges)) {
                                Event::dispatch(new FootballSportsScoreUpdated);
                            }

                            $flushTimer = null;
                        });
                    }
                }
            } catch (Throwable $e) {
                $isDuplicateConnection = str_contains($e->getMessage(), 'duplicate WebSocket connection');
                Log::log($isDuplicateConnection ? 'warning' : 'error', 'Betfair Odds WebSocket disconnected', [
                    'message' => $e->getMessage(),
                    'retry_in_seconds' => $backoff,
                ]);
                $this->error('Betfair Odds WebSocket disconnected: '.$e->getMessage());
            } finally {
                if ($flushTimer !== null) {
                    EventLoop::cancel($flushTimer);
                }

                if ($this->flushPendingChanges($pendingChanges)) {
                    Event::dispatch(new FootballSportsScoreUpdated);
                }
            }

            delay($backoff);
            $backoff = min($backoff * 2, 30);
        }
    }

    private function flushPendingChanges(array &$pendingChanges): bool
    {
        $updated = false;

        foreach (['live' => true, 'prematch' => false] as $feed => $isLive) {
            if ($pendingChanges[$feed] === []) {
                continue;
            }

            $events = $pendingChanges[$feed];
            $pendingChanges[$feed] = [];
            $feedUpdated = withRedisLock(
                'Football:matches',
                fn (): bool => $this->updateFootballCache($events, $isLive)
            );
            $updated = $feedUpdated || $updated;
        }

        return $updated;
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
            if (($match['data_sync_complete'] ?? false) === true && isset($match['betfair_match_id'])) {
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
        $databaseMarkets = [];
        $missingMarketMatchKeys = [];

        foreach (array_keys($events) as $eventId) {
            $matchKey = $matchKeys[(string) $eventId] ?? null;
            if ($matchKey !== null && empty($matches[$matchKey]['markets'])) {
                $missingMarketMatchKeys[$matchKey] = true;
            }
        }

        if ($missingMarketMatchKeys !== []) {
            $databaseRows = DB::table('football_markets')
                ->whereIn('football_match_unique_key', array_keys($missingMarketMatchKeys))
                ->get(['football_match_unique_key', 'payload']);

            foreach ($databaseRows as $databaseRow) {
                $payload = json_decode($databaseRow->payload, true);
                if (is_array($payload)) {
                    $databaseMarkets[$databaseRow->football_match_unique_key][] = $payload;
                }
            }
        }

        foreach ($events as $eventId => $event) {
            $matchKey = $matchKeys[(string) $eventId] ?? null;
            if ($matchKey === null || ! is_array($event['markets'] ?? null)) {
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
                $sortPriority = $runner['sortPriority'] ?? null;
                $previousRunner = $previousMarkets[$marketKey][$sortPriority] ?? [];

                if ($previousRunner === []) {
                    foreach ($previousMarkets as $previousMarketRunners) {
                        foreach (($previousMarketRunners ?? []) as $candidate) {
                            $sameRunner = (
                                isset($runner['runnerId'], $candidate['runnerId'])
                                && (string) $candidate['runnerId'] === (string) $runner['runnerId']
                            ) || strcasecmp((string) ($candidate['runner'] ?? ''), (string) ($runner['runner'] ?? '')) === 0;

                            if (
                                ($candidate['marketType'] ?? null) === ($runner['marketType'] ?? null)
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
                            ($candidate['marketType'] ?? null) === ($runner['marketType'] ?? null)
                            && $sameRunner
                            && (float) ($candidate['handicap'] ?? 0) === (float) ($runner['handicap'] ?? 0)
                        ) {
                            $previousRunner = $candidate;
                            break;
                        }
                    }
                }

                $previousOdds = (float) ($previousRunner['currentBackOdds'] ?? $previousRunner['backOdds'] ?? $runner['backOdds'] ?? 0);
                $receivedOdds = (float) ($runner['backOdds'] ?? 0);
                $currentOdds = $receivedOdds > 0 ? $receivedOdds : $previousOdds;
                $backOddsHistory = $previousRunner['backOddsHistory'] ?? [];
                $initialOddsTimestamp = $previousRunner['initialBackOddsTimestamp'] ?? null;

                if ($initialOddsTimestamp !== null && isset($previousRunner['initialBackOdds'])) {
                    $initialOdds = (float) $previousRunner['initialBackOdds'];
                } elseif (isset($backOddsHistory[0]['odds'])) {
                    $initialOdds = (float) $backOddsHistory[0]['odds'];
                    $initialOddsTimestamp = (int) ($backOddsHistory[0]['timestamp'] ?? $now);
                } elseif (isset($previousRunner['initialBackOdds']) && (float) $previousRunner['initialBackOdds'] > 0) {
                    $initialOdds = (float) $previousRunner['initialBackOdds'];
                    $initialOddsTimestamp = $now;
                } else {
                    $initialOdds = null;
                    $initialOddsTimestamp = null;
                }

                if ($backOddsHistory === [] && $initialOdds !== null && $initialOdds > 0) {
                    $backOddsHistory[] = ['timestamp' => $now, 'odds' => $initialOdds];
                }

                $lastHistoryOdds = (float) ($backOddsHistory[array_key_last($backOddsHistory)]['odds'] ?? 0);
                if ($initialOdds !== null && $currentOdds > 0 && $currentOdds !== $lastHistoryOdds) {
                    $backOddsHistory[] = ['timestamp' => $now, 'odds' => $currentOdds];
                }

                $runner['previousBackOdds'] = $previousOdds;
                $runner['currentBackOdds'] = $currentOdds;
                unset($runner['initialBackOdds'], $runner['initialBackOddsTimestamp']);
                if ($initialOdds !== null) {
                    $runner['initialBackOdds'] = $initialOdds;
                    $runner['initialBackOddsTimestamp'] = $initialOddsTimestamp;
                }
                $runner['backOddsHistory'] = array_slice($backOddsHistory, -12);
                $runner['odds_direction'] = $initialOdds === null || $initialOdds <= 0 || $currentOdds === $initialOdds
                    ? 'same'
                    : ($currentOdds < $initialOdds ? 'down' : 'up');

                if (! $isLive && $currentOdds > 0) {
                    $baselineOdds = (float) ($previousRunner['prematch_drop_baseline_odds'] ?? 0);
                    if ($baselineOdds <= 0 && ($marketLiquidity[$marketKey] ?? 0) >= 1000) {
                        $baselineOdds = $currentOdds;
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
        }

        if ($updated) {
            storeInRedis('Football:matches', $matches, 30);
        }

        return $updated;
    }
}
