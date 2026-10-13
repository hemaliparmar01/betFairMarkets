<?php

namespace App\Console\Commands;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Websocket\Client\WebsocketHandshake;
use App\Events\CricketSportsScoreUpdated;
use App\Http\Controllers\Users\Cricket\CricketSyncController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;
use function Amp\Websocket\Client\connect;

class ListenCricketSportsApiPro extends Command
{
    protected $signature = 'cricket:listen';

    protected $description = 'Listen to SportsAPIPro cricket updates';

    public function handle(): int
    {
        $listenerLockSeconds = 300;
        $listenerLock = Cache::store('redis')->lock('Cricket:sports-api-pro-listener', $listenerLockSeconds);
        if (! $listenerLock->get()) {
            $this->warn('SportsAPI Pro listener is already running.');

            return self::SUCCESS;
        }

        $hasListenerLock = true;
        $lastListenerLockRefreshAt = time();
        $releaseListenerLock = static function () use ($listenerLock): void {
            try {
                $listenerLock->release();
            } catch (Throwable $e) {
                Log::error('SportsAPI Pro listener singleton lock release failed.', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        };

        $stopListener = static function (string $watcherId) use ($releaseListenerLock): void {
            EventLoop::cancel($watcherId);
            $releaseListenerLock();

            exit(self::SUCCESS);
        };

        EventLoop::onSignal(SIGINT, $stopListener);
        EventLoop::onSignal(SIGTERM, $stopListener);

        EventLoop::repeat(30, function () use ($listenerLock, $listenerLockSeconds, &$hasListenerLock, &$lastListenerLockRefreshAt): void {
            try {
                $hasListenerLock = $listenerLock->refresh($listenerLockSeconds) || $listenerLock->get();

                if ($hasListenerLock) {
                    $lastListenerLockRefreshAt = time();

                    return;
                }

                Log::warning('SportsAPI Pro listener singleton lock is unavailable; the listener will wait and retry without stopping.');
            } catch (Throwable $e) {
                $hasListenerLock = false;

                Log::error('SportsAPI Pro listener singleton lock refresh failed; the listener will retry without stopping.', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        });
        register_shutdown_function($releaseListenerLock);

        $key = GetSportApiProKey();

        if (! $key) {
            $this->error('SPORTS_API_PRO_KEY is missing.');

            return self::FAILURE;
        }

        $backoff = 1;
        $httpClient = (new HttpClientBuilder)->retry(0)->build();
        $initialLiveRecoveryQueued = false;

        while (true) {
            while (! $hasListenerLock) {
                try {
                    $hasListenerLock = $listenerLock->get();

                    if ($hasListenerLock) {
                        $lastListenerLockRefreshAt = time();
                        Log::info('SportsAPI Pro listener singleton lock was reacquired.');

                        break;
                    }
                } catch (Throwable $e) {
                    Log::error('SportsAPI Pro listener singleton lock reacquisition failed.', [
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);
                }

                delay(5);
            }

            $pingTimer = null;
            $subscriptionRefreshTimer = null;
            $matchSubscriptions = [];

            try {
                $handshake = (new WebsocketHandshake('wss://api.sportsapipro.com/v2/cricket/ws'))->withHeader('x-api-key', $key);

                $socket = connect($handshake);
                $backoff = 1;

                $socket->sendText(json_encode([
                    'action' => 'subscribe',
                    'channel' => 'live-scores',
                ], JSON_THROW_ON_ERROR));

                $this->info('Connected; subscribed to live-scores.');

                if (! $initialLiveRecoveryQueued || Cache::store('redis')->add('Cricket:sports-api-pro-live-recovery', time(), 300)) {
                    $initialLiveRecoveryQueued = true;
                    Cache::store('redis')->put('Cricket:sports-api-pro-live-recovery', time(), 300);

                    try {
                        dispatch(function (): void {
                            try {
                                $recoveredMatches = app(CricketSyncController::class)->syncSportsApiProLiveData() ?? [];

                                try {
                                    Event::dispatch(new CricketSportsScoreUpdated);
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

                foreach (getInRedis('Cricket:matches') ?? [] as $match) {
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

                    foreach (getInRedis('Cricket:matches') ?? [] as $match) {
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

                            recordSportsApiProRestRequest('/v2/cricket/api/match/{matchId}');

                            $request = new Request("https://api.sportsapipro.com/v2/cricket/api/match/{$matchId}");
                            $request->setHeader('x-api-key', $key);
                            $request->setTcpConnectTimeout(3);
                            $request->setTransferTimeout(7);
                            $request->setInactivityTimeout(7);

                            $response = $httpClient->request($request);
                            if ($response->getStatus() < 200 || $response->getStatus() >= 300) {
                                throw new \RuntimeException("SportsAPI Pro match endpoint returned HTTP {$response->getStatus()}.");
                            }

                            $payload = json_decode($response->getBody()->buffer(null, 2_000_000), true, 512, JSON_THROW_ON_ERROR);
                            $restMatch = $payload['match'] ?? $payload['event'] ?? $payload['data']['match'] ?? $payload['data']['event'] ?? (isset($payload['data']['id']) ? $payload['data'] : null);
                            if (! is_array($restMatch)) {
                                throw new \RuntimeException('SportsAPI Pro match endpoint returned an invalid payload.');
                            }

                            $restMatch['id'] = $restMatch['id'] ?? $payload['matchId'] ?? $matchId;
                            if ((string) $restMatch['id'] !== (string) $matchId) {
                                throw new \RuntimeException('SportsAPI Pro match endpoint returned a different match ID.');
                            }

                            $reconciliation = withRedisLock('Cricket:matches', function () use ($matchId, $restMatch): array {
                                $matches = getInRedis('Cricket:matches') ?? [];
                                $matchFound = false;

                                foreach ($matches as $match) {
                                    if (
                                        ($match['data_sync_complete'] ?? false) === true
                                        && ($match['result_status'] ?? null) === 'Live'
                                        && (string) ($match['sportsApiPro_match_id'] ?? '') === (string) $matchId
                                    ) {
                                        $matchFound = true;
                                        break;
                                    }
                                }

                                if (! $matchFound) {
                                    return ['found' => false, 'updated' => false];
                                }

                                $updated = $this->updateLiveMatch($matches, $restMatch, "match:{$matchId}:reconciliation");
                                if ($updated) {
                                    storeInRedis('Cricket:matches', $matches, 30);
                                    app(CricketSyncController::class)->persistMatches($matches);
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
                                if ($reconciliation['updated']) {
                                    try {
                                        Event::dispatch(new CricketSportsScoreUpdated);
                                    } catch (Throwable $e) {
                                        Log::warning('Cricket finished reconciliation broadcast failed', ['exception' => $e::class]);
                                    }
                                }

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
                                    Event::dispatch(new CricketSportsScoreUpdated);
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
                    if (time() - $lastListenerLockRefreshAt >= 30) {
                        try {
                            $hasListenerLock = $listenerLock->refresh($listenerLockSeconds) || $listenerLock->get();

                            if ($hasListenerLock) {
                                $lastListenerLockRefreshAt = time();
                            }
                        } catch (Throwable $e) {
                            $hasListenerLock = false;

                            Log::error('SportsAPI Pro listener singleton lock heartbeat failed.', [
                                'exception' => $e::class,
                                'message' => $e->getMessage(),
                            ]);
                        }

                        if (! $hasListenerLock) {
                            Log::warning('SportsAPI Pro listener paused because its singleton lock is unavailable; it will retry without stopping.');

                            break;
                        }
                    }

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

                        $updated = withRedisLock('Cricket:matches', fn (): bool => $this->updateCricketCache($channel, $frameData));

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

                        if (! $updated) {
                            continue;
                        }
                        try {
                            Event::dispatch(new CricketSportsScoreUpdated);
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
                    if (Cache::store('redis')->add('Cricket:sports-api-pro-disconnect-recovery', time(), 60)) {
                        Cache::store('redis')->put('Cricket:sports-api-pro-live-recovery', time(), 300);

                        dispatch(function (): void {
                            try {
                                $recoveredMatches = app(CricketSyncController::class)->syncSportsApiProLiveData() ?? [];

                                try {
                                    Event::dispatch(new CricketSportsScoreUpdated);
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

        if (isset($frameData['id']) || isset($frameData['eventId'])) {
            return [$frameData];
        }

        if (isset($frameData['events']) && is_array($frameData['events'])) {
            return $frameData['events'];
        }

        return array_is_list($frameData) ? $frameData : [];
    }

    private function updateCricketCache(string $channel, mixed $frameData): bool
    {
        if (is_array($frameData)) {
            return app(CricketSyncController::class)->mergeSportsWebSocketData($channel, $frameData);
        }

        return false;
    }

    private function updateLiveMatch(array &$matches, array $event, string $channel): bool
    {
        $id = $event['id'] ?? $event['eventId'] ?? null;
        foreach ($matches as &$match) {
            if ((string) ($match['sportsApiPro_match_id'] ?? '') === (string) $id) {
                return app(CricketSyncController::class)->applySportsApiEvent($match, $event, $channel);
            }
        }
        unset($match);

        return false;
    }

    private function syncedMatchIds(): array
    {
        $matches = getInRedis('Cricket:matches');
        $matchIds = [];

        foreach ($matches ?? [] as $match) {
            if (
                ($match['data_sync_complete'] ?? false) !== true
                || ($match['result_status'] ?? null) !== 'Live'
                || ! isset($match['sportsApiPro_match_id'])
            ) {
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
}
