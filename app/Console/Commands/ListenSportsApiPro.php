<?php

namespace App\Console\Commands;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Websocket\Client\WebsocketHandshake;
use App\Events\FootballSportsScoreUpdated;
use App\Http\Controllers\Users\Football\FootballSyncController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;
use function Amp\Websocket\Client\connect;

class ListenSportsApiPro extends Command
{
    protected $signature = 'sports:listen';

    protected $description = 'Listen to SportsAPIPro football updates';

    public function handle(): int
    {
        $listenerLock = Cache::store('redis')->lock('Football:sports-api-pro-listener', 120);
        if (! $listenerLock->get()) {
            $this->warn('SportsAPI Pro listener is already running.');

            return self::SUCCESS;
        }

        EventLoop::repeat(60, function () use ($listenerLock): void {
            if (! $listenerLock->refresh(120)) {
                Log::critical('SportsAPI Pro listener lost its singleton lock and is stopping.');
                exit(self::FAILURE);
            }
        });
        register_shutdown_function(static fn () => $listenerLock->release());

        $key = GetSportApiProKey();

        if (! $key) {
            $this->error('SPORTS_API_PRO_KEY is missing.');

            return self::FAILURE;
        }

        $backoff = 1;
        $httpClient = (new HttpClientBuilder)->retry(0)->build();
        $initialLiveRecoveryQueued = false;

        while (true) {
            $pingTimer = null;
            $subscriptionRefreshTimer = null;
            $matchSubscriptions = [];

            try {
                $handshake = (new WebsocketHandshake('wss://api.sportsapipro.com/v2/football/ws'))->withHeader('x-api-key', $key);

                $socket = connect($handshake);
                $backoff = 1;

                $socket->sendText(json_encode([
                    'action' => 'subscribe',
                    'channel' => 'live-scores',
                ], JSON_THROW_ON_ERROR));

                $this->info('Connected; subscribed to live-scores.');

                if (! $initialLiveRecoveryQueued || Cache::store('redis')->add('Football:sports-api-pro-live-recovery', time(), 300)) {
                    $initialLiveRecoveryQueued = true;
                    Cache::store('redis')->put('Football:sports-api-pro-live-recovery', time(), 300);

                    try {
                        dispatch(function (): void {
                            try {
                                $recoveredMatches = app(FootballSyncController::class)->syncSportsApiProLiveData() ?? [];

                                try {
                                    Event::dispatch(new FootballSportsScoreUpdated);
                                } catch (Throwable $e) {
                                    Log::warning('SportsAPI Pro startup recovery broadcast failed', [
                                        'message' => $e->getMessage(),
                                    ]);
                                }

                                Log::info('SportsAPI Pro startup live-state recovery completed', [
                                    'matches' => count($recoveredMatches),
                                ]);
                            } catch (Throwable $e) {
                                Log::warning('SportsAPI Pro startup live-state recovery failed', [
                                    'message' => $e->getMessage(),
                                ]);
                            }
                        })->onQueue('default');
                    } catch (Throwable $e) {
                        Log::warning('SportsAPI Pro startup live-state recovery could not be queued', [
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                foreach (getInRedis('Football:matches') ?? [] as $match) {
                    if (
                        ($match['data_sync_complete'] ?? false) !== true
                        || ($match['result_status'] ?? null) !== 'Live'
                        || ! isset($match['sportsApiPro_match_id'])
                    ) {
                        continue;
                    }

                    $matchId = (string) $match['sportsApiPro_match_id'];
                    foreach (["match:{$matchId}", "match:{$matchId}:incidents", "match:{$matchId}:stats"] as $channel) {
                        $socket->sendText(json_encode([
                            'action' => 'subscribe',
                            'channel' => $channel,
                        ], JSON_THROW_ON_ERROR));
                    }
                    $matchSubscriptions[$matchId] = [
                        'last_frame_at' => time(),
                        'last_reconciled_at' => 0,
                        'reconciling' => false,
                    ];
                }

                $pingTimer = EventLoop::repeat(30, function () use ($socket): void {
                    $socket->sendText(json_encode([
                        'action' => 'ping',
                        'channel' => 'live-scores',
                    ], JSON_THROW_ON_ERROR));
                });

                $subscriptionRefreshTimer = EventLoop::repeat(30, function () use ($socket, $key, $httpClient, &$matchSubscriptions): void {
                    $now = time();
                    $staleBefore = $now - 120;
                    $reconciliationCooldown = $now - 300;
                    $reconciliationsThisCycle = 0;
                    $activeMatchIds = [];

                    foreach (getInRedis('Football:matches') ?? [] as $match) {
                        if (
                            ($match['data_sync_complete'] ?? false) === true
                            && ($match['result_status'] ?? null) === 'Live'
                            && isset($match['sportsApiPro_match_id'])
                        ) {
                            $activeMatchIds[(string) $match['sportsApiPro_match_id']] = true;
                        }
                    }

                    foreach (array_keys($activeMatchIds) as $matchId) {
                        if (isset($matchSubscriptions[$matchId])) {
                            continue;
                        }

                        try {
                            foreach (["match:{$matchId}", "match:{$matchId}:incidents", "match:{$matchId}:stats"] as $channel) {
                                $socket->sendText(json_encode([
                                    'action' => 'subscribe',
                                    'channel' => $channel,
                                ], JSON_THROW_ON_ERROR));
                            }
                            $matchSubscriptions[$matchId] = [
                                'last_frame_at' => $now,
                                'last_reconciled_at' => 0,
                                'reconciling' => false,
                            ];
                        } catch (Throwable $e) {
                            Log::warning('SportsAPI Pro active match subscription failed', [
                                'match_id' => $matchId,
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }

                    foreach (array_keys($matchSubscriptions) as $matchId) {
                        if ($reconciliationsThisCycle >= 1) {
                            break;
                        }

                        $subscription = $matchSubscriptions[$matchId];
                        if (
                            ($subscription['last_frame_at'] ?? $now) > $staleBefore
                            || ($subscription['reconciling'] ?? false)
                            || ($subscription['last_reconciled_at'] ?? 0) > $reconciliationCooldown
                        ) {
                            continue;
                        }

                        if (! isset($activeMatchIds[(string) $matchId])) {
                            try {
                                foreach (["match:{$matchId}", "match:{$matchId}:incidents", "match:{$matchId}:stats"] as $channel) {
                                    $socket->sendText(json_encode([
                                        'action' => 'unsubscribe',
                                        'channel' => $channel,
                                    ], JSON_THROW_ON_ERROR));
                                }
                            } catch (Throwable $e) {
                                Log::warning('SportsAPI Pro inactive match unsubscribe failed', [
                                    'match_id' => $matchId,
                                    'message' => $e->getMessage(),
                                ]);
                            }
                            unset($matchSubscriptions[$matchId]);

                            continue;
                        }

                        $matchSubscriptions[$matchId]['reconciling'] = true;
                        $matchSubscriptions[$matchId]['last_reconciled_at'] = $now;
                        $reconciliationsThisCycle++;

                        try {
                            Log::warning('SportsAPI Pro match subscription is stale; reconciling from REST', [
                                'match_id' => $matchId,
                                'seconds_since_last_frame' => $now - (int) ($subscription['last_frame_at'] ?? $now),
                            ]);

                            recordSportsApiProRestRequest('/v2/football/api/match/{matchId}');

                            $request = new Request("https://api.sportsapipro.com/v2/football/api/match/{$matchId}");
                            $request->setHeader('x-api-key', $key);
                            $request->setTcpConnectTimeout(3);
                            $request->setTransferTimeout(7);
                            $request->setInactivityTimeout(7);

                            $response = $httpClient->request($request);
                            if ($response->getStatus() < 200 || $response->getStatus() >= 300) {
                                throw new \RuntimeException("SportsAPI Pro match endpoint returned HTTP {$response->getStatus()}.");
                            }

                            $payload = json_decode($response->getBody()->buffer(null, 2_000_000), true, 512, JSON_THROW_ON_ERROR);
                            $restMatch = $payload['match'] ?? null;
                            if (! is_array($restMatch)) {
                                throw new \RuntimeException('SportsAPI Pro match endpoint returned an invalid payload.');
                            }

                            $restMatch['id'] = $restMatch['id'] ?? $payload['matchId'] ?? $matchId;
                            if ((string) $restMatch['id'] !== (string) $matchId) {
                                throw new \RuntimeException('SportsAPI Pro match endpoint returned a different match ID.');
                            }

                            $reconciliation = withRedisLock('Football:matches', function () use ($matchId, $restMatch): array {
                                $matches = getInRedis('Football:matches') ?? [];
                                $matchFound = false;

                                foreach ($matches as $match) {
                                    if (($match['data_sync_complete'] ?? false) === true && (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId) {
                                        $matchFound = true;
                                        break;
                                    }
                                }

                                if (! $matchFound) {
                                    return ['found' => false, 'updated' => false];
                                }

                                $updated = $this->updateLiveMatch($matches, $restMatch, "match:{$matchId}:reconciliation");
                                if ($updated) {
                                    storeInRedis('Football:matches', $matches, 10);
                                }

                                return ['found' => true, 'updated' => $updated];
                            });

                            $channels = [
                                "match:{$matchId}",
                                "match:{$matchId}:incidents",
                                "match:{$matchId}:stats",
                            ];

                            foreach ($channels as $channel) {
                                $socket->sendText(json_encode([
                                    'action' => 'unsubscribe',
                                    'channel' => $channel,
                                ], JSON_THROW_ON_ERROR));
                            }

                            $statusCode = $this->eventValue($restMatch, 'status.code');
                            $statusType = $this->eventValue($restMatch, 'status.type');
                            if (! $reconciliation['found'] || $statusCode == 100 || $statusType === 'finished') {
                                unset($matchSubscriptions[$matchId]);

                                continue;
                            }

                            foreach ($channels as $channel) {
                                $socket->sendText(json_encode([
                                    'action' => 'subscribe',
                                    'channel' => $channel,
                                ], JSON_THROW_ON_ERROR));
                            }

                            $matchSubscriptions[$matchId]['last_frame_at'] = time();

                            Log::info('SportsAPI Pro stale match reconciliation completed', [
                                'match_id' => $matchId,
                                'state_updated' => $reconciliation['updated'],
                            ]);

                            if ($reconciliation['updated']) {
                                try {
                                    Event::dispatch(new FootballSportsScoreUpdated);
                                } catch (Throwable $e) {
                                    Log::warning('SportsAPI Pro reconciliation broadcast failed', [
                                        'match_id' => $matchId,
                                        'message' => $e->getMessage(),
                                    ]);
                                }
                            }
                        } catch (Throwable $e) {
                            Log::warning('SportsAPI Pro stale match reconciliation failed', [
                                'match_id' => $matchId,
                                'message' => $e->getMessage(),
                            ]);
                        } finally {
                            if (isset($matchSubscriptions[$matchId])) {
                                $matchSubscriptions[$matchId]['reconciling'] = false;
                            }
                        }
                    }
                });

                foreach ($socket as $message) {
                    $frame = json_decode($message->buffer(), true);

                    if (! is_array($frame)) {
                        continue;
                    }

                    $type = $frame['type'] ?? 'unknown';
                    Log::info('TYPE :  '.$type);

                    if (in_array($type, ['snapshot', 'update'], true)) {
                        $frameData = $frame['data'] ?? null;
                        $channel = $frame['channel'] ?? '';

                        Log::info('SportsAPI Pro WebSocket event received', [
                            'type' => $type,
                            'channel' => $channel,
                            'timestamp' => $frame['timestamp'] ?? null,
                            'data' => $frameData,
                        ]);

                        if (preg_match('/^match:(\d+)(?::(?:incidents|stats))?$/', $channel, $channelMatch) && isset($matchSubscriptions[$channelMatch[1]])) {
                            $matchSubscriptions[$channelMatch[1]]['last_frame_at'] = time();
                        }

                        $liveEvents = str_starts_with($channel, 'live-scores')
                            ? $this->normalizeLiveEvents($frameData)
                            : [];
                        $syncedMatchIds = $this->syncedMatchIds();

                        withRedisLock('Football:matches', function () use ($channel, $frameData): void {
                            $this->updateFootballCache($channel, $frameData);
                        });

                        foreach ($liveEvents as $liveEvent) {
                            $matchId = $liveEvent['id'] ?? $liveEvent['eventId'] ?? null;
                            if ($matchId === null || ! isset($syncedMatchIds[(string) $matchId])) {
                                continue;
                            }

                            if (isset($matchSubscriptions[(string) $matchId])) {
                                $matchSubscriptions[(string) $matchId]['last_frame_at'] = time();
                            }

                            if (
                                $this->eventValue($liveEvent, 'status.code') == 100
                                || $this->eventValue($liveEvent, 'status.type') === 'finished'
                            ) {
                                foreach (["match:{$matchId}", "match:{$matchId}:incidents", "match:{$matchId}:stats"] as $subscriptionChannel) {
                                    $socket->sendText(json_encode([
                                        'action' => 'unsubscribe',
                                        'channel' => $subscriptionChannel,
                                    ], JSON_THROW_ON_ERROR));
                                }
                                unset($matchSubscriptions[$matchId]);

                                continue;
                            }

                            if (! isset($matchSubscriptions[$matchId])) {
                                foreach (["match:{$matchId}", "match:{$matchId}:incidents", "match:{$matchId}:stats"] as $subscriptionChannel) {
                                    $socket->sendText(json_encode([
                                        'action' => 'subscribe',
                                        'channel' => $subscriptionChannel,
                                    ], JSON_THROW_ON_ERROR));
                                }
                                $matchSubscriptions[$matchId] = [
                                    'last_frame_at' => time(),
                                    'last_reconciled_at' => 0,
                                    'reconciling' => false,
                                ];
                            }
                        }

                        try {
                            Event::dispatch(new FootballSportsScoreUpdated);
                        } catch (Throwable $e) {
                            Log::warning('SportsAPI Pro live update broadcast failed', [
                                'channel' => $channel,
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (Throwable $e) {
                $this->error('WebSocket disconnected: '.$e->getMessage());

                try {
                    if (Cache::store('redis')->add('Football:sports-api-pro-disconnect-recovery', time(), 60)) {
                        Cache::store('redis')->put('Football:sports-api-pro-live-recovery', time(), 300);

                        dispatch(function (): void {
                            try {
                                $recoveredMatches = app(FootballSyncController::class)->syncSportsApiProLiveData() ?? [];

                                try {
                                    Event::dispatch(new FootballSportsScoreUpdated);
                                } catch (Throwable $broadcastException) {
                                    Log::warning('SportsAPI Pro disconnect recovery broadcast failed', [
                                        'message' => $broadcastException->getMessage(),
                                    ]);
                                }

                                Log::info('SportsAPI Pro disconnect live-state recovery completed', [
                                    'matches' => count($recoveredMatches),
                                ]);
                            } catch (Throwable $recoveryException) {
                                Log::warning('SportsAPI Pro disconnect live-state recovery failed', [
                                    'message' => $recoveryException->getMessage(),
                                ]);
                            }
                        })->onQueue('default');
                    }
                } catch (Throwable $recoveryQueueException) {
                    Log::warning('SportsAPI Pro disconnect live-state recovery could not be queued', [
                        'message' => $recoveryQueueException->getMessage(),
                    ]);
                }
            } finally {
                if ($pingTimer !== null) {
                    EventLoop::cancel($pingTimer);
                }
                if ($subscriptionRefreshTimer !== null) {
                    EventLoop::cancel($subscriptionRefreshTimer);
                }
            }

            delay($backoff);
            $backoff = min($backoff * 2, 30);
        }
    }

    private function normalizeLiveEvents(mixed $frameData): array
    {
        if (! is_array($frameData)) {
            return [];
        }

        if (isset($frameData['id'])) {
            return [$frameData];
        }

        if (isset($frameData['events']) && is_array($frameData['events'])) {
            return $frameData['events'];
        }

        return array_is_list($frameData) ? $frameData : [];
    }

    private function updateFootballCache(string $channel, mixed $frameData): void
    {
        $matches = getInRedis('Football:matches');
        if (empty($matches) || ! is_array($frameData)) {
            return;
        }

        if (str_starts_with($channel, 'live-scores')) {
            foreach ($this->normalizeLiveEvents($frameData) as $event) {
                $this->updateLiveMatch($matches, $event, $channel);
            }
        } elseif (preg_match('/^match:(\d+)$/', $channel)) {
            $this->updateLiveMatch($matches, $frameData, $channel);
        } elseif (preg_match('/^match:(\d+):incidents$/', $channel, $channelMatch)) {
            $this->updateMatchIncidents($matches, $channelMatch[1], $frameData, $channel);
        } elseif (preg_match('/^match:(\d+):stats$/', $channel, $channelMatch)) {
            $this->updateMatchStats($matches, $channelMatch[1], $frameData, $channel);
        }

        foreach ($matches as &$match) {
            unset($match['events'], $match['goals'], $match['goal_counts'], $match['status_description']);
            if (($match['minutes_status'] ?? null) === 'Finished') {
                $match['result_status'] = 'Finished';
            } elseif (! in_array($match['result_status'] ?? null, ['Live', 'Prematch', 'Finished'], true)) {
                $match['result_status'] = match ($match['minutes_status'] ?? null) {
                    'Prematch' => 'Prematch',
                    'Finished' => 'Finished',
                    default => 'Live',
                };
            }

            $match['minutes_status'] = match ($match['result_status']) {
                'Finished' => 'Finished',
                'Prematch' => 'Prematch',
                'Live' => in_array($match['minutes_status'] ?? null, ['Live', 'Half Time', '1st half', '2nd half'], true)
                    ? $match['minutes_status']
                    : 'Live',
            };
        }
        unset($match);

        storeInRedis('Football:matches', $matches, 10);
    }

    private function updateLiveMatch(array &$matches, array $event, string $channel): bool
    {
        $matchId = $event['id'] ?? $event['eventId'] ?? null;
        if ($matchId === null) {
            return false;
        }

        foreach ($matches as &$match) {
            if (($match['data_sync_complete'] ?? false) !== true || ($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if ($this->isDuplicateChange($match, $event, $channel)) {
                return false;
            }

            $previousState = [
                'home_score' => $match['home_score'] ?? null,
                'away_score' => $match['away_score'] ?? null,
                'result_status' => $match['result_status'] ?? null,
                'minutes_status' => $match['minutes_status'] ?? null,
                'current_period_start_timestamp' => $match['current_period_start_timestamp'] ?? null,
                'last_period_end_timestamp' => $match['last_period_end_timestamp'] ?? null,
                'added_time' => $match['added_time'] ?? null,
                'home_team_id' => $match['home_team_id'] ?? null,
                'away_team_id' => $match['away_team_id'] ?? null,
                'tournament_id' => $match['tournament_id'] ?? null,
                'country_id' => $match['country_id'] ?? null,
            ];

            $homeScore = $this->eventValue($event, 'homeScore.current');
            if ($homeScore !== null) {
                $match['home_score'] = $homeScore;
            }

            $awayScore = $this->eventValue($event, 'awayScore.current');
            if ($awayScore !== null) {
                $match['away_score'] = $awayScore;
            }

            foreach ([
                'home_team_id' => 'homeTeam.id',
                'away_team_id' => 'awayTeam.id',
                'tournament_id' => 'tournament.id',
                'country_id' => 'tournament.category.country.id',
            ] as $cacheKey => $eventKey) {
                $identifier = $this->eventValue($event, $eventKey);
                if (is_numeric($identifier) && (int) $identifier > 0) {
                    $match[$cacheKey] = (int) $identifier;
                }
            }

            $currentPeriodStartTimestamp = $this->eventValue($event, 'time.currentPeriodStartTimestamp');
            if (is_numeric($currentPeriodStartTimestamp) && (int) $currentPeriodStartTimestamp > 0) {
                $match['current_period_start_timestamp'] = (int) $currentPeriodStartTimestamp;
            }

            $lastPeriodEndTimestamp = $this->eventValue($event, 'time.lastPeriodEndTimestamp');
            if (is_numeric($lastPeriodEndTimestamp) && (int) $lastPeriodEndTimestamp > 0) {
                $match['last_period_end_timestamp'] = (int) $lastPeriodEndTimestamp;
            }

            $firstHalfAddedTime = $this->eventValue($event, 'time.injuryTime1');
            if (is_numeric($firstHalfAddedTime) && (int) $firstHalfAddedTime > 0) {
                $match['added_time'] = is_array($match['added_time'] ?? null) ? $match['added_time'] : [];
                $match['added_time']['first_half'] = (int) $firstHalfAddedTime;
            }

            $secondHalfAddedTime = $this->eventValue($event, 'time.injuryTime2');
            if (is_numeric($secondHalfAddedTime) && (int) $secondHalfAddedTime > 0) {
                $match['added_time'] = is_array($match['added_time'] ?? null) ? $match['added_time'] : [];
                $match['added_time']['second_half'] = (int) $secondHalfAddedTime;
            }

            $statusCode = $this->eventValue($event, 'status.code');
            $statusType = $this->eventValue($event, 'status.type');
            if ($statusCode == 6 && ($match['result_status'] ?? null) !== 'Finished') {
                $match['minutes_status'] = '1st half';
                $match['result_status'] = 'Live';
            } elseif ($statusCode == 7 && ($match['result_status'] ?? null) !== 'Finished') {
                $match['minutes_status'] = '2nd half';
                $match['result_status'] = 'Live';
            } elseif ($statusCode == 31 && ($match['result_status'] ?? null) !== 'Finished') {
                $match['minutes_status'] = 'Half Time';
                $match['result_status'] = 'Live';
            } elseif ($statusCode == 100 || $statusType === 'finished') {
                $match['minutes_status'] = 'Finished';
                $match['result_status'] = 'Finished';
            } elseif ($statusCode !== null && ! in_array((int) $statusCode, [6, 7, 31], true)) {
                if (! in_array($match['minutes_status'] ?? null, ['Live', 'Prematch', 'Finished', 'Half Time', '1st half', '2nd half'], true)) {
                    $match['minutes_status'] = 'Live';
                }
                Log::warning('Unhandled SportsAPI Pro football status', [
                    'match_id' => $matchId,
                    'status_code' => $statusCode,
                    // 'status_type' => $statusType,
                ]);
            }

            // $match['status_type'] = $statusType;
            if (
                ($match['result_status'] ?? null) === 'Live'
                && ($match['minutes_status'] ?? null) === 'Live'
                && (int) floor((time() - (int) ($match['timestamp'] ?? time())) / 60) >= 60
            ) {
                $match['minutes_status'] = '2nd half';
            }
            unset($match['status_description']);

            return $previousState !== [
                'home_score' => $match['home_score'] ?? null,
                'away_score' => $match['away_score'] ?? null,
                'result_status' => $match['result_status'] ?? null,
                'minutes_status' => $match['minutes_status'] ?? null,
                'current_period_start_timestamp' => $match['current_period_start_timestamp'] ?? null,
                'last_period_end_timestamp' => $match['last_period_end_timestamp'] ?? null,
                'added_time' => $match['added_time'] ?? null,
                'home_team_id' => $match['home_team_id'] ?? null,
                'away_team_id' => $match['away_team_id'] ?? null,
                'tournament_id' => $match['tournament_id'] ?? null,
                'country_id' => $match['country_id'] ?? null,
            ];
        }
        unset($match);

        return false;
    }

    private function updateMatchIncidents(array &$matches, int|string $matchId, array $frameData, string $channel): void
    {
        foreach ($matches as &$match) {
            if (($match['data_sync_complete'] ?? false) !== true || ($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if ($this->isDuplicateChange($match, $frameData, $channel)) {
                return;
            }

            $incidents = $this->normalizeIncidents($frameData);
            $cards = ['home' => null, 'away' => null];
            $cardCounts = [
                'home' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
                'away' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
            ];
            $addedTime = ['first_half' => null, 'second_half' => null];
            $latestGoal = null;

            foreach ($incidents as $incident) {
                $incidentType = $incident['incidentType'] ?? null;

                if ($incidentType === 'goal') {
                    if ($latestGoal === null || ($incident['timeSeconds'] ?? 0) > ($latestGoal['timeSeconds'] ?? 0)) {
                        $latestGoal = $incident;
                    }

                    continue;
                }

                if ($incidentType === 'injuryTime' && isset($incident['length'], $incident['time'])) {
                    $period = $incident['time'] <= 45 ? 'first_half' : 'second_half';
                    $addedTime[$period] = (int) $incident['length'];

                    continue;
                }

                if ($incidentType !== 'card' || ! array_key_exists('isHome', $incident)) {
                    continue;
                }

                $incidentClass = $incident['incidentClass'] ?? null;
                if (! in_array($incidentClass, ['yellow', 'red', 'yellowRed'], true)) {
                    continue;
                }

                $side = $incident['isHome'] ? 'home' : 'away';
                if ($incidentClass === 'yellow') {
                    $cardCounts[$side]['yellow']++;
                } elseif ($incidentClass === 'red') {
                    $cardCounts[$side]['direct_red']++;
                    $cardCounts[$side]['total_red']++;
                } else {
                    $cardCounts[$side]['second_yellow']++;
                    $cardCounts[$side]['total_red']++;
                }

                if (in_array($incidentClass, ['red', 'yellowRed'], true)) {
                    $cards[$side] = 'red';
                } elseif ($cards[$side] === null) {
                    $cards[$side] = $incidentClass;
                }
            }

            if ($latestGoal !== null) {
                $latestGoalId = (string) ($latestGoal['id'] ?? implode(':', [
                    $latestGoal['timeSeconds'] ?? 0,
                    $latestGoal['homeScore'] ?? 0,
                    $latestGoal['awayScore'] ?? 0,
                    ! empty($latestGoal['isHome']) ? 'home' : 'away',
                ]));
                $previousGoal = $match['last_goal'] ?? null;
                $previousScoreTotal = ($previousGoal['home_score'] ?? 0) + ($previousGoal['away_score'] ?? 0);
                $latestScoreTotal = ($latestGoal['homeScore'] ?? 0) + ($latestGoal['awayScore'] ?? 0);

                if (
                    ($match['incidents_initialized'] ?? false) === true
                    && ($previousGoal['id'] ?? null) !== $latestGoalId
                    && ($previousGoal === null || $latestScoreTotal > $previousScoreTotal)
                ) {
                    $match['goal_highlight_until'] = time() + 40;
                }

                $match['last_goal'] = [
                    'id' => $latestGoalId,
                    'is_home' => (bool) ($latestGoal['isHome'] ?? false),
                    'time' => $latestGoal['time'] ?? null,
                    'home_score' => $latestGoal['homeScore'] ?? null,
                    'away_score' => $latestGoal['awayScore'] ?? null,
                ];

                if (isset($latestGoal['homeScore'])) {
                    $match['home_score'] = $latestGoal['homeScore'];
                }
                if (isset($latestGoal['awayScore'])) {
                    $match['away_score'] = $latestGoal['awayScore'];
                }
            } else {
                unset($match['last_goal']);
            }

            $match['incidents_initialized'] = true;
            $match['cards'] = $cards;
            $match['card_counts'] = $cardCounts;
            $match['added_time'] = $addedTime;

            return;
        }
        unset($match);
    }

    private function normalizeIncidents(array $frameData): array
    {
        if (isset($frameData['incidents']) && is_array($frameData['incidents'])) {
            return $frameData['incidents'];
        }

        if (isset($frameData['events']) && is_array($frameData['events'])) {
            return $frameData['events'];
        }

        if (array_is_list($frameData)) {
            return $frameData;
        }

        if (! isset($frameData['incidentType'])) {
            return [];
        }

        return [$frameData];
    }

    private function updateMatchStats(array &$matches, int|string $matchId, array $frameData, string $channel): void
    {
        foreach ($matches as &$match) {
            if (($match['data_sync_complete'] ?? false) !== true || ($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if ($this->isDuplicateChange($match, $frameData, $channel)) {
                return;
            }

            $statKeys = [
                'attacks' => 'attacks',
                'dangerousattacks' => 'dangerous_attacks',
                'ballpossession' => 'possession',
                'possession' => 'possession',
                'totalshotsongoal' => 'total_shots',
                'shotsongoal' => 'shots_on_target',
                'shotsoffgoal' => 'shots_off_target',
                'blockedscoringattempt' => 'blocked_shots',
                'passes' => 'total_passes',
                'accuratepasses' => 'accurate_passes',
                'totaltackle' => 'tackles',
                'interceptionwon' => 'interceptions',
                'fouls' => 'fouls',
                'cornerkicks' => 'corners',
                'yellowcards' => 'yellow_cards',
                'redcards' => 'red_cards',
                'offsides' => 'offsides',
            ];
            $liveStats = [];
            $stack = [[$frameData, true]];

            while ($stack !== []) {
                [$data, $includePeriod] = array_pop($stack);
                if (! is_array($data)) {
                    continue;
                }

                $period = strtoupper((string) ($data['period'] ?? $data['periodName'] ?? ''));
                if (in_array($period, ['1ST', '2ND', 'FIRST', 'SECOND'], true)) {
                    $includePeriod = false;
                } elseif ($period === 'ALL') {
                    $includePeriod = true;
                }

                $name = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) ($data['name'] ?? $data['statisticName'] ?? $data['key'] ?? '')));
                if ($includePeriod && isset($statKeys[$name])) {
                    $home = $data['homeValue'] ?? $data['home'] ?? null;
                    $away = $data['awayValue'] ?? $data['away'] ?? null;
                    $home = is_string($home) ? rtrim(trim($home), '%') : $home;
                    $away = is_string($away) ? rtrim(trim($away), '%') : $away;

                    if (is_numeric($home) && is_numeric($away)) {
                        $key = $statKeys[$name];
                        $liveStats[$key] = [
                            'home' => (float) $home,
                            'away' => (float) $away,
                        ];
                    }
                }

                foreach ($data as $value) {
                    if (is_array($value)) {
                        $stack[] = [$value, $includePeriod];
                    }
                }
            }

            $match['live_stats'] = $liveStats;
            if (isset($liveStats['corners'])) {
                $match['corners'] = [
                    'home' => (int) $liveStats['corners']['home'],
                    'away' => (int) $liveStats['corners']['away'],
                ];
            }

            return;
        }
        unset($match);
    }

    private function syncedMatchIds(): array
    {
        $matches = getInRedis('Football:matches');
        $matchIds = [];

        foreach ($matches ?? [] as $match) {
            if (($match['data_sync_complete'] ?? false) !== true || ! isset($match['sportsApiPro_match_id'])) {
                continue;
            }
            $matchIds[(string) $match['sportsApiPro_match_id']] = true;
        }

        return $matchIds;
    }

    private function eventValue(array $event, string $key): mixed
    {
        if (array_key_exists($key, $event)) {
            return $event[$key];
        }

        $value = $event;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private function isDuplicateChange(array &$match, array $data, string $channel): bool
    {
        $changeTimestamp = $this->eventValue($data, 'changes.changeTimestamp');
        if ($changeTimestamp === null) {
            return false;
        }

        $lastChangeTimestamp = $match['websocket_change_timestamps'][$channel] ?? null;
        if ($lastChangeTimestamp !== null && $changeTimestamp <= $lastChangeTimestamp) {
            return true;
        }

        $match['websocket_change_timestamps'][$channel] = $changeTimestamp;

        return false;
    }
}
