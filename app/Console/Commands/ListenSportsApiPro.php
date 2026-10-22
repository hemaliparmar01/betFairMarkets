<?php

namespace App\Console\Commands;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\CompositeCancellation;
use Amp\DeferredCancellation;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Websocket\Client\WebsocketConnectException;
use Amp\Websocket\Client\WebsocketConnection;
use Amp\Websocket\Client\WebsocketHandshake;
use App\Events\AmericanFootballSportsScoreUpdated;
use App\Events\BaseballSportsScoreUpdated;
use App\Events\BasketballSportsScoreUpdated;
use App\Events\CricketSportsScoreUpdated;
use App\Events\FootballSportsScoreUpdated;
use App\Events\IceHockeySportsScoreUpdated;
use App\Events\TennisSportsScoreUpdated;
use App\Http\Controllers\Users\AmericanFootball\AmericanFootballSyncController;
use App\Http\Controllers\Users\Baseball\BaseballSyncController;
use App\Http\Controllers\Users\Basketball\BasketballSyncController;
use App\Http\Controllers\Users\Cricket\CricketSyncController;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Http\Controllers\Users\IceHockey\IceHockeySyncController;
use App\Http\Controllers\Users\Tennis\TennisSyncController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\awaitAll;
use function Amp\Websocket\Client\connect;

class ListenSportsApiPro extends Command
{
    protected $signature = 'sports-api-pro:listen';

    protected $aliases = ['sports:listen'];

    protected $description = 'Listen to all supported SportsAPI Pro sports in parallel';

    private const SPORTS = [
        'football' => ['prefix' => 'Football', 'path' => 'football', 'header' => null, 'controller' => FootballSyncController::class, 'event' => FootballSportsScoreUpdated::class, 'detail' => 'incidents', 'finished_codes' => [100], 'finished_types' => ['finished']],
        'cricket' => ['prefix' => 'Cricket', 'path' => 'cricket', 'header' => null, 'controller' => CricketSyncController::class, 'event' => CricketSportsScoreUpdated::class, 'detail' => 'incidents', 'finished_codes' => [100], 'finished_types' => ['finished']],
        'icehockey' => ['prefix' => 'IceHockey', 'path' => 'hockey', 'header' => 'ice-hockey', 'controller' => IceHockeySyncController::class, 'event' => IceHockeySportsScoreUpdated::class, 'detail' => 'incidents', 'finished_codes' => [100], 'finished_types' => ['finished']],
        'americanfootball' => ['prefix' => 'AmericanFootball', 'path' => 'american-football', 'header' => 'american-football', 'controller' => AmericanFootballSyncController::class, 'event' => AmericanFootballSportsScoreUpdated::class, 'detail' => 'incidents', 'finished_codes' => [100, 110], 'finished_types' => ['finished']],
        'baseball' => ['prefix' => 'Baseball', 'path' => 'baseball', 'header' => 'baseball', 'controller' => BaseballSyncController::class, 'event' => BaseballSportsScoreUpdated::class, 'detail' => 'incidents', 'finished_codes' => [100, 110], 'finished_types' => ['finished']],
        'basketball' => ['prefix' => 'Basketball', 'path' => 'basketball', 'header' => 'basketball', 'controller' => BasketballSyncController::class, 'event' => BasketballSportsScoreUpdated::class, 'detail' => 'incidents', 'finished_codes' => [100, 110], 'finished_types' => ['finished']],
        'tennis' => ['prefix' => 'Tennis', 'path' => 'tennis', 'header' => 'tennis', 'controller' => TennisSyncController::class, 'event' => TennisSportsScoreUpdated::class, 'detail' => 'point-by-point', 'finished_codes' => [80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 110], 'finished_types' => ['finished', 'abandoned']],
    ];

    public function handle(): int
    {
        $key = GetSportApiProKey();
        if (! $key) {
            $this->error('SPORTS_API_PRO_KEY is missing.');

            return self::FAILURE;
        }

        $shutdown = new DeferredCancellation;
        $releases = [];
        $releaseAll = static function () use (&$releases): void {
            foreach ($releases as $release) {
                $release();
            }
        };
        register_shutdown_function($releaseAll);
        $signals = [];
        foreach ([SIGINT, SIGTERM] as $signal) {
            $signals[] = EventLoop::onSignal($signal, static function () use ($shutdown): void {
                $shutdown->cancel();
            });
        }

        try {
            $futures = [];
            foreach (self::SPORTS as $sport => $config) {
                $startupDelay = count($futures) + random_int(0, 500) / 1000;
                $futures[$sport] = async(function () use ($sport, $config, $key, $shutdown, $startupDelay, &$releases): void {
                    try {
                        delay($startupDelay, cancellation: $shutdown->getCancellation());
                    } catch (CancelledException) {
                        return;
                    }
                    $backoff = 5;
                    while (! $shutdown->isCancelled()) {
                        try {
                            $this->listenSport($sport, $config, $key, $shutdown->getCancellation(), $releases);

                            return;
                        } catch (Throwable $e) {
                            if ($shutdown->isCancelled()) {
                                return;
                            }
                            $retryDelay = $backoff + random_int(0, 1000) / 1000;
                            Log::error('SportsAPI Pro sport listener failed; retrying', ['sport' => $sport, 'exception' => $e::class, 'message' => $e->getMessage(), 'retry_in_seconds' => $retryDelay]);
                            try {
                                delay($retryDelay, cancellation: $shutdown->getCancellation());
                                $backoff = $backoff === 5 ? 10 : 30;
                            } catch (CancelledException) {
                                return;
                            }
                        }
                    }
                });
            }
            awaitAll($futures);

            return self::SUCCESS;
        } finally {
            $shutdown->cancel();
            $releaseAll();
            foreach ($signals as $signal) {
                EventLoop::cancel($signal);
            }
        }
    }

    /**
     * @param  array{prefix: string, path: string, header: ?string, controller: class-string, event: class-string, detail: string, finished_codes: list<int>, finished_types: list<string>}  $config
     * @param  array<string, callable(): void>  $releases
     */
    private function listenSport(string $sport, array $config, string $key, Cancellation $shutdown, array &$releases): void
    {
        $prefix = $config['prefix'];
        $cacheKey = $prefix.':matches';
        $listenerLock = Cache::store('redis')->lock($prefix.':sports-api-pro-listener', 300);
        if (! $listenerLock->get()) {
            $this->warn("[{$sport}] SportsAPI Pro listener is already running.");

            return;
        }

        $socket = null;
        $lockTimer = null;
        $connectionFailure = null;
        $hasListenerLock = true;
        $release = static function () use ($sport, $listenerLock, &$socket, &$lockTimer): void {
            if ($lockTimer !== null) {
                EventLoop::cancel($lockTimer);
                $lockTimer = null;
            }
            try {
                $socket?->close();
                $socket = null;
            } catch (Throwable $e) {
                Log::warning('SportsAPI Pro socket shutdown failed', ['sport' => $sport, 'exception' => $e::class]);
            } finally {
                try {
                    $listenerLock->release();
                } catch (Throwable $e) {
                    Log::error('SportsAPI Pro listener singleton lock release failed', ['sport' => $sport, 'exception' => $e::class]);
                }
            }
        };
        $releases[$sport] = $release;

        try {
            $lockTimer = EventLoop::repeat(30, function () use ($sport, $listenerLock, &$hasListenerLock, &$connectionFailure): void {
                try {
                    $hasListenerLock = $listenerLock->refresh(300) || $listenerLock->get();
                    if (! $hasListenerLock) {
                        throw new \RuntimeException('The sport listener no longer owns its singleton lock.');
                    }
                } catch (Throwable $e) {
                    $hasListenerLock = false;
                    $connectionFailure?->cancel($e);
                    Log::warning('SportsAPI Pro listener lock unavailable; waiting to reacquire', ['sport' => $sport, 'exception' => $e::class]);
                }
            });
            $httpClient = (new HttpClientBuilder)->retry(0)->build();
            $backoff = 5;
            $initialRecoveryQueued = false;

            while (! $shutdown->isRequested()) {
                while (! $hasListenerLock && ! $shutdown->isRequested()) {
                    $hasListenerLock = $listenerLock->get();
                    if (! $hasListenerLock) {
                        delay(5, cancellation: $shutdown);
                    }
                }
                $shutdown->throwIfRequested();
                $connectionFailure = new DeferredCancellation;
                $cancellation = new CompositeCancellation($shutdown, $connectionFailure->getCancellation());
                $timers = [];
                $matchSubscriptions = [];
                $connectedAt = null;
                $rateLimited = false;

                try {
                    while (($retryAt = (int) Cache::store('redis')->get('SportsAPIPro:websocket-retry-at', 0)) > time()) {
                        delay(max(1, $retryAt - time()) + random_int(0, 3), cancellation: $cancellation);
                    }
                    $handshake = (new WebsocketHandshake("wss://api.sportsapipro.com/v2/{$config['path']}/ws"))->withHeader('x-api-key', $key);
                    if ($config['header'] !== null) {
                        $handshake = $handshake->withHeader('x-sport', $config['header']);
                    }
                    $socket = connect($handshake, $cancellation);
                    $connectedAt = microtime(true);
                    $socket->sendText(json_encode(['action' => 'subscribe', 'channel' => 'live-scores'], JSON_THROW_ON_ERROR));
                    $this->info("[{$sport}] Connected; subscribed to live-scores.");
                    $this->queueLiveRecovery($sport, $config, 'startup', ! $initialRecoveryQueued);
                    $initialRecoveryQueued = true;

                    foreach (array_keys($this->syncedMatchIds($cacheKey)) as $matchId) {
                        foreach ($this->matchChannels($sport, $matchId, $config['detail']) as $channel) {
                            $socket->sendText(json_encode(['action' => 'subscribe', 'channel' => $channel], JSON_THROW_ON_ERROR));
                        }
                        $matchSubscriptions[$matchId] = ['last_frame_at' => time(), 'last_reconciled_at' => 0, 'reconciling' => false];
                        delay(0, cancellation: $cancellation);
                    }

                    $timers[] = EventLoop::repeat(30, static function () use ($socket, $connectionFailure, $cancellation): void {
                        if ($cancellation->isRequested()) {
                            return;
                        }
                        try {
                            $socket->sendText(json_encode(['action' => 'ping', 'channel' => 'live-scores'], JSON_THROW_ON_ERROR));
                        } catch (Throwable $e) {
                            $connectionFailure->cancel($e);
                        }
                    });
                    $timers[] = EventLoop::repeat(30, function () use ($sport, $config, $key, $socket, $httpClient, $connectionFailure, $cancellation, &$matchSubscriptions): void {
                        if ($cancellation->isRequested()) {
                            return;
                        }
                        try {
                            $this->refreshMatchSubscriptions($sport, $config, $key, $socket, $httpClient, $matchSubscriptions, $cancellation);
                        } catch (Throwable $e) {
                            Log::warning('SportsAPI Pro subscription watchdog failed', ['sport' => $sport, 'exception' => $e::class, 'message' => $e->getMessage()]);
                            $connectionFailure->cancel($e);
                        }
                    });

                    while (($message = $socket->receive($cancellation)) !== null) {
                        $frame = json_decode($message->buffer($cancellation), true);
                        if (! is_array($frame)) {
                            continue;
                        }
                        $this->processSportsFrame($sport, $config, $frame, $socket, $matchSubscriptions);
                        delay(0, cancellation: $cancellation);
                    }
                    throw new \RuntimeException('The remote WebSocket closed the connection.');
                } catch (Throwable $e) {
                    if ($shutdown->isRequested()) {
                        break;
                    }
                    if ($connectedAt !== null && microtime(true) - $connectedAt >= 60) {
                        $backoff = 5;
                    }
                    $rateLimited = $e instanceof WebsocketConnectException && $e->getResponse()->getStatus() === 429;
                    if ($rateLimited) {
                        $retryAfter = $e->getResponse()->getHeader('retry-after');
                        $retrySeconds = is_numeric($retryAfter) ? (int) $retryAfter : max(0, (strtotime($retryAfter ?? '') ?: time()) - time());
                        $backoff = max(60, $backoff, $retrySeconds);
                        Cache::store('redis')->put('SportsAPIPro:websocket-retry-at', time() + $backoff, $backoff);
                    }
                    $retryDelay = $backoff + random_int(0, 1000) / 1000;
                    $this->error("[{$sport}] WebSocket disconnected: ".$e->getMessage());
                    Log::warning('SportsAPI Pro WebSocket disconnected', ['sport' => $sport, 'exception' => $e::class, 'message' => $e->getMessage(), 'retry_in_seconds' => $retryDelay]);
                    if ($hasListenerLock) {
                        $this->queueLiveRecovery($sport, $config, 'disconnect');
                    }
                } finally {
                    $connectionFailure->cancel();
                    foreach ($timers as $timer) {
                        EventLoop::cancel($timer);
                    }
                    try {
                        $socket?->close();
                    } catch (Throwable $e) {
                        Log::warning('SportsAPI Pro socket close failed', ['sport' => $sport, 'exception' => $e::class]);
                    }
                    $socket = null;
                }
                if (! $shutdown->isRequested()) {
                    delay($retryDelay, cancellation: $shutdown);
                    $backoff = $rateLimited ? min($backoff * 2, 600) : ($backoff === 5 ? 10 : 30);
                }
            }
        } finally {
            $release();
            unset($releases[$sport]);
        }
    }

    /**
     * @param  array{prefix: string, controller: class-string, event: class-string}  $config
     */
    private function queueLiveRecovery(string $sport, array $config, string $reason, bool $force = false): void
    {
        $pendingLock = null;
        try {
            $lockName = $config['prefix'].':sports-api-pro-recovery-pending';
            $pendingLock = Cache::store('redis')->lock($lockName, 3600);
            if (! $pendingLock->get()) {
                return;
            }
            $gate = $config['prefix'].':sports-api-pro-'.($reason === 'disconnect' ? 'disconnect-recovery' : 'live-recovery');
            if (! $force && ! Cache::store('redis')->add($gate, time(), $reason === 'disconnect' ? 60 : 300)) {
                $pendingLock->release();

                return;
            }
            Cache::store('redis')->put($config['prefix'].':sports-api-pro-live-recovery', time(), 300);
            $controllerClass = $config['controller'];
            $eventClass = $config['event'];
            $owner = $pendingLock->owner();
            dispatch(static function () use ($sport, $controllerClass, $eventClass, $reason, $lockName, $owner): void {
                $lock = Cache::store('redis')->restoreLock($lockName, $owner);
                try {
                    if (! $lock->isOwnedByCurrentProcess()) {
                        return;
                    }
                    $matches = app($controllerClass)->syncSportsApiProLiveData() ?? [];
                    try {
                        Event::dispatch(new $eventClass);
                    } catch (Throwable $e) {
                        Log::warning('SportsAPI Pro recovery broadcast failed', ['sport' => $sport, 'exception' => $e::class]);
                    }
                    Log::info('SportsAPI Pro live-state recovery completed', ['sport' => $sport, 'reason' => $reason, 'matches' => count($matches)]);
                } catch (Throwable $e) {
                    Log::warning('SportsAPI Pro live-state recovery failed', ['sport' => $sport, 'reason' => $reason, 'exception' => $e::class, 'message' => $e->getMessage()]);
                } finally {
                    $lock->release();
                }
            })->onQueue('default')->catch(static function (Throwable $e) use ($lockName, $owner): void {
                Cache::store('redis')->restoreLock($lockName, $owner)->release();
            });
        } catch (Throwable $e) {
            $pendingLock?->release();
            Log::warning('SportsAPI Pro live-state recovery could not be queued', ['sport' => $sport, 'reason' => $reason, 'exception' => $e::class]);
        }
    }

    /**
     * @param  array{prefix: string, controller: class-string, event: class-string, detail: string, finished_codes: list<int>, finished_types: list<string>}  $config
     * @param  array<string|int, array{last_frame_at: int, last_reconciled_at: int, reconciling: bool}>  $subscriptions
     */
    private function processSportsFrame(string $sport, array $config, array $frame, WebsocketConnection $socket, array &$subscriptions): void
    {
        $type = $frame['type'] ?? 'unknown';
        $channel = (string) ($frame['channel'] ?? '');
        Log::info('TYPE :  '.$type, ['sport' => $sport]);
        if (preg_match('/^match:(\d+)(?::.+)?$/', $channel, $match) && isset($subscriptions[$match[1]])) {
            $subscriptions[$match[1]]['last_frame_at'] = time();
        }
        if (! in_array($type, ['snapshot', 'update'], true)) {
            return;
        }
        $frameData = $frame['data'] ?? null;
        Log::info('SportsAPI Pro WebSocket event received', ['sport' => $sport, 'type' => $type, 'channel' => $channel, 'timestamp' => $frame['timestamp'] ?? null, 'data' => $frameData]);
        if (! is_array($frameData)) {
            return;
        }
        if ($sport === 'football' && preg_match('/^match:(\d+):odds$/', $channel, $oddsChannel)) {
            try {
                app(FootballSyncController::class)->mergeBet365LiveOdds($oddsChannel[1], $frameData, sourceTimestamp: is_numeric($frame['timestamp'] ?? null) ? (int) $frame['timestamp'] : null);
            } catch (Throwable $e) {
                Log::warning('Football bet365 live odds update failed; socket remains connected', ['channel' => $channel, 'exception' => $e::class]);
            }

            return;
        }
        if ($sport === 'football' && preg_match('/^match:\d+(?::(?:incidents|stats))?$/', $channel)) {
            try {
                app(FootballSyncController::class)->mergeTimelineFrame($channel, $frameData, $type);
            } catch (Throwable $e) {
                Log::warning('Football timeline frame could not be cached', ['channel' => $channel, 'exception' => $e::class]);
            }
        }
        $cacheKey = $config['prefix'].':matches';
        $syncedIds = $this->syncedMatchIds($cacheKey);
        $updated = withRedisLock($cacheKey, function () use ($sport, $config, $channel, $frameData): bool {
            if ($sport === 'football') {
                $this->updateFootballCache($channel, $frameData);

                return true;
            }

            return app($config['controller'])->mergeSportsWebSocketData($channel, $frameData);
        });
        $events = str_starts_with($channel, 'live-scores') ? $this->normalizeLiveEvents($frameData) : [];
        foreach ($events as $event) {
            $matchId = $event['id'] ?? $event['eventId'] ?? null;
            if ($matchId === null || ! isset($syncedIds[(string) $matchId])) {
                continue;
            }
            if ($sport === 'football') {
                try {
                    app(FootballSyncController::class)->mergeTimelineFrame('match:'.$matchId, $event, $type);
                } catch (Throwable $e) {
                    Log::warning('Football timeline clock could not be cached', ['match_id' => $matchId, 'exception' => $e::class]);
                }
            }
            $finished = in_array((int) $this->eventValue($event, 'status.code'), $config['finished_codes'], true)
                || in_array($this->eventValue($event, 'status.type'), $config['finished_types'], true);
            if (isset($subscriptions[$matchId])) {
                $subscriptions[$matchId]['last_frame_at'] = time();
            }
            if ($finished || ! isset($subscriptions[$matchId])) {
                foreach ($this->matchChannels($sport, $matchId, $config['detail']) as $subscriptionChannel) {
                    $socket->sendText(json_encode(['action' => $finished ? 'unsubscribe' : 'subscribe', 'channel' => $subscriptionChannel], JSON_THROW_ON_ERROR));
                }
                if ($finished) {
                    unset($subscriptions[$matchId]);
                } else {
                    $subscriptions[$matchId] = ['last_frame_at' => time(), 'last_reconciled_at' => 0, 'reconciling' => false];
                }
            }
        }
        if ($updated) {
            try {
                Event::dispatch(new $config['event']);
            } catch (Throwable $e) {
                Log::warning('SportsAPI Pro live update broadcast failed', ['sport' => $sport, 'channel' => $channel, 'exception' => $e::class]);
            }
        }
    }

    /**
     * @param  array{prefix: string, path: string, controller: class-string, event: class-string, detail: string, finished_codes: list<int>, finished_types: list<string>}  $config
     * @param  array<string|int, array{last_frame_at: int, last_reconciled_at: int, reconciling: bool}>  $subscriptions
     */
    private function refreshMatchSubscriptions(string $sport, array $config, string $key, WebsocketConnection $socket, HttpClient $httpClient, array &$subscriptions, Cancellation $cancellation): void
    {
        $now = time();
        $cacheKey = $config['prefix'].':matches';
        $activeIds = $this->syncedMatchIds($cacheKey);
        $channels = fn (string|int $id): array => $this->matchChannels($sport, $id, $config['detail']);
        foreach (array_keys($activeIds) as $matchId) {
            if (isset($subscriptions[$matchId])) {
                continue;
            }
            foreach ($channels($matchId) as $channel) {
                $socket->sendText(json_encode(['action' => 'subscribe', 'channel' => $channel], JSON_THROW_ON_ERROR));
            }
            $subscriptions[$matchId] = ['last_frame_at' => $now, 'last_reconciled_at' => 0, 'reconciling' => false];
        }
        $reconciled = 0;
        foreach (array_keys($subscriptions) as $matchId) {
            $cancellation->throwIfRequested();
            if (! isset($activeIds[(string) $matchId])) {
                foreach ($channels($matchId) as $channel) {
                    $socket->sendText(json_encode(['action' => 'unsubscribe', 'channel' => $channel], JSON_THROW_ON_ERROR));
                }
                unset($subscriptions[$matchId]);

                continue;
            }
            $subscription = $subscriptions[$matchId];
            if ($reconciled >= 1 || $subscription['last_frame_at'] > $now - 120 || $subscription['reconciling'] || $subscription['last_reconciled_at'] > $now - 300) {
                continue;
            }
            $subscriptions[$matchId]['reconciling'] = true;
            $subscriptions[$matchId]['last_reconciled_at'] = $now;
            $reconciled++;
            try {
                Log::warning('SportsAPI Pro match subscription is stale; reconciling from REST', ['sport' => $sport, 'match_id' => $matchId, 'seconds_since_last_frame' => $now - $subscription['last_frame_at']]);
                recordSportsApiProRestRequest("/v2/{$config['path']}/api/match/{matchId}");
                $request = new Request("https://api.sportsapipro.com/v2/{$config['path']}/api/match/{$matchId}");
                $request->setHeader('x-api-key', $key);
                $request->setTcpConnectTimeout(3);
                $request->setTransferTimeout(7);
                $request->setInactivityTimeout(7);
                $response = $httpClient->request($request, $cancellation);
                if ($response->getStatus() < 200 || $response->getStatus() >= 300) {
                    throw new \RuntimeException("SportsAPI Pro match endpoint returned HTTP {$response->getStatus()}.");
                }
                $payload = json_decode($response->getBody()->buffer($cancellation, 2_000_000), true, 512, JSON_THROW_ON_ERROR);
                $restMatch = $payload['match'] ?? $payload['event'] ?? $payload['data']['match'] ?? $payload['data']['event'] ?? (isset($payload['data']['id']) ? $payload['data'] : null);
                if (! is_array($restMatch)) {
                    throw new \RuntimeException('SportsAPI Pro match endpoint returned an invalid payload.');
                }
                $restMatch['id'] = $restMatch['id'] ?? $payload['matchId'] ?? $matchId;
                if ((string) $restMatch['id'] !== (string) $matchId) {
                    throw new \RuntimeException('SportsAPI Pro match endpoint returned a different match ID.');
                }
                $cancellation->throwIfRequested();
                $updated = withRedisLock($cacheKey, function () use ($sport, $config, $cacheKey, $matchId, $restMatch): bool {
                    $matches = getInRedis($cacheKey) ?? [];
                    foreach ($matches as &$match) {
                        if (($sport !== 'football' && ($match['data_sync_complete'] ?? false) !== true) || ($match['result_status'] ?? '') !== 'Live' || (string) ($match['sportsApiPro_match_id'] ?? '') !== (string) $matchId) {
                            continue;
                        }
                        if ($sport === 'football') {
                            $updated = $this->updateLiveMatch($matches, $restMatch, "match:{$matchId}:reconciliation");
                        } else {
                            $updated = app($config['controller'])->applySportsApiEvent($match, $restMatch, "match:{$matchId}:reconciliation");
                        }
                        unset($match);
                        if ($updated) {
                            storeInRedis($cacheKey, $matches, $sport === 'football' ? 10 : 30);
                            if ($sport !== 'football') {
                                app($config['controller'])->persistMatches($matches);
                            }
                        }

                        return $updated;
                    }

                    return false;
                });
                foreach ($channels($matchId) as $channel) {
                    $socket->sendText(json_encode(['action' => 'unsubscribe', 'channel' => $channel], JSON_THROW_ON_ERROR));
                }
                $finished = in_array((int) $this->eventValue($restMatch, 'status.code'), $config['finished_codes'], true)
                    || in_array($this->eventValue($restMatch, 'status.type'), $config['finished_types'], true);
                if ($finished || ! isset($this->syncedMatchIds($cacheKey)[(string) $matchId])) {
                    unset($subscriptions[$matchId]);
                } else {
                    foreach ($channels($matchId) as $channel) {
                        $socket->sendText(json_encode(['action' => 'subscribe', 'channel' => $channel], JSON_THROW_ON_ERROR));
                    }
                    $subscriptions[$matchId]['last_frame_at'] = time();
                }
                if ($updated) {
                    try {
                        Event::dispatch(new $config['event']);
                    } catch (Throwable $e) {
                        Log::warning('SportsAPI Pro reconciliation broadcast failed', ['sport' => $sport, 'exception' => $e::class]);
                    }
                }
            } catch (Throwable $e) {
                if ($cancellation->isRequested()) {
                    throw $e;
                }
                Log::warning('SportsAPI Pro stale match reconciliation failed', ['sport' => $sport, 'match_id' => $matchId, 'exception' => $e::class, 'message' => $e->getMessage()]);
            } finally {
                if (isset($subscriptions[$matchId])) {
                    $subscriptions[$matchId]['reconciling'] = false;
                }
            }
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

    /** @return list<string> */
    private function matchChannels(string $sport, int|string $matchId, string $detail): array
    {
        $channels = ["match:{$matchId}", "match:{$matchId}:{$detail}", "match:{$matchId}:stats"];
        if ($sport === 'football') {
            $channels[] = "match:{$matchId}:odds";
        }

        return $channels;
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
            if (($match['result_status'] ?? null) === 'Finished') {
                continue;
            }

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
            if (($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if (($match['result_status'] ?? null) === 'Finished') {
                return false;
            }

            $statusCode = $this->eventValue($event, 'status.code');
            $statusType = $this->eventValue($event, 'status.type');
            if ($this->isDuplicateChange($match, $event, $channel)) {
                if ($statusCode == 100 || $statusType === 'finished') {
                    $match['minutes_status'] = 'Finished';
                    $match['result_status'] = 'Finished';

                    return true;
                }

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

            $tournamentCountry = $this->eventValue($event, 'tournament.category.name');
            if (is_string($tournamentCountry) && $tournamentCountry !== '') {
                $match['tournament_country'] = $tournamentCountry;
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

            if ($match['result_status'] === 'Live' && $previousState['result_status'] === 'Prematch' && isset($match['bet365_prematch']['match_id'])) {
                app(FootballSyncController::class)->queueBet365MatchOdds($match, (int) $match['bet365_prematch']['match_id']);
            }

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
            if (($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if (($match['result_status'] ?? null) === 'Finished') {
                return;
            }

            $incidents = $this->normalizeIncidents($frameData);
            $finishedPeriod = array_find($incidents, static fn (array $incident): bool => ($incident['incidentType'] ?? null) === 'period'
                && strtoupper(trim((string) ($incident['text'] ?? ''))) === 'FT'
                && ($incident['isLive'] ?? null) === false
            );
            $isDuplicateChange = $this->isDuplicateChange($match, $frameData, $channel);
            if ($isDuplicateChange && $finishedPeriod === null) {
                return;
            }

            if ($finishedPeriod !== null) {
                $match['minutes_status'] = 'Finished';
                $match['result_status'] = 'Finished';
                if (isset($finishedPeriod['homeScore'])) {
                    $match['home_score'] = $finishedPeriod['homeScore'];
                }
                if (isset($finishedPeriod['awayScore'])) {
                    $match['away_score'] = $finishedPeriod['awayScore'];
                }
            }

            if ($isDuplicateChange) {
                return;
            }

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

                $incidentClass = $incident['incClass'] ?? $incident['incidentClass'] ?? null;
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

            $timeline = Cache::store('redis')->get('Football:timeline:'.$matchId, []);
            if ($timeline['initialized'] ?? false) {
                $cards = ['home' => null, 'away' => null];
                $cardCounts = [
                    'home' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
                    'away' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
                ];
                foreach ($timeline['events'] ?? [] as $incident) {
                    if (! in_array($incident['kind'] ?? null, ['yellow', 'red'], true)) {
                        continue;
                    }
                    $side = $incident['is_home'] ? 'home' : 'away';
                    if ($incident['kind'] === 'yellow') {
                        $cardCounts[$side]['yellow']++;
                        $cards[$side] ??= 'yellow';
                    } else {
                        $cardCounts[$side][($incident['incident_class'] ?? '') === 'yellowRed' ? 'second_yellow' : 'direct_red']++;
                        $cardCounts[$side]['total_red']++;
                        $cards[$side] = 'red';
                    }
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

                if ($finishedPeriod === null && isset($latestGoal['homeScore'])) {
                    $match['home_score'] = $latestGoal['homeScore'];
                }
                if ($finishedPeriod === null && isset($latestGoal['awayScore'])) {
                    $match['away_score'] = $latestGoal['awayScore'];
                }
            } else {
                unset($match['last_goal']);
            }

            $match['incidents_initialized'] = true;
            $match['cards'] = $cards;
            $match['card_counts'] = $cardCounts;
            $match['cards_updated_at'] = microtime(true);
            $match['card_counts_updated_at'] = $match['cards_updated_at'];
            foreach (['yellow_cards', 'red_cards'] as $stat) {
                if (($match['live_stats_sources'][$stat] ?? null) === 'v6') {
                    unset($match['live_stats'][$stat], $match['live_stats_sources'][$stat]);
                }
            }
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
            if (($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if (($match['result_status'] ?? null) === 'Finished') {
                return;
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

            $match['live_stats'] = array_replace($match['live_stats'] ?? [], $liveStats);
            foreach (array_keys($liveStats) as $stat) {
                $match['live_stats_sources'][$stat] = 'v2';
            }
            if (isset($liveStats['yellow_cards']) || isset($liveStats['red_cards'])) {
                $match['cards_updated_at'] = microtime(true);
                $match['card_stats_updated_at'] = $match['cards_updated_at'];
            }
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

    private function syncedMatchIds(string $cacheKey): array
    {
        $matches = getInRedis($cacheKey);
        $matchIds = [];

        foreach ($matches ?? [] as $match) {
            if (
                ($cacheKey !== 'Football:matches' && ($match['data_sync_complete'] ?? false) !== true)
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
