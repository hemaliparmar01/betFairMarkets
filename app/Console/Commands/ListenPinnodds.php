<?php

namespace App\Console\Commands;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use App\Http\Controllers\Users\Football\FootballSyncController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

class ListenPinnodds extends Command
{
    protected $signature = 'pinnodds:listen';

    protected $description = 'Listen to Pinnodds live and prematch drops and share them through Redis';

    public function handle(): int
    {
        $key = (string) config('services.pinnodds.key');
        if ($key === '') {
            $this->error('PINACLE_ODDS_KEY is missing.');

            return self::FAILURE;
        }

        $listenerLock = Cache::store('redis')->lock('Pinnacle:listener', 120);
        if (! $listenerLock->get()) {
            $this->warn('Pinnodds listener is already running.');

            return self::SUCCESS;
        }

        $pending = [];
        $feeds = array_fill_keys(['live', 'prematch'], ['status' => 'connecting', 'last_frame_at' => 0]);
        $bodies = [];
        $dirty = true;
        $lastFlushAt = 0;
        $client = (new HttpClientBuilder)->retry(0)->build();
        $flush = function () use (&$pending, &$feeds, &$dirty, &$lastFlushAt): void {
            if (! $dirty && time() - $lastFlushAt < 30) {
                return;
            }
            try {
                $this->storeDrops($pending, $feeds);
            } catch (Throwable $e) {
                Log::warning('Pinnodds cache update failed', ['exception' => $e::class]);

                return;
            }
            try {
                app(FootballSyncController::class)->mergePinnacleDrops(array_values($pending));
                $pending = [];
            } catch (Throwable $e) {
                Log::warning('Pinnacle Football filter update deferred; pending signals retained', ['exception' => $e::class]);
            }
            $dirty = false;
            $lastFlushAt = time();
            try {
                Broadcast::on('sports.pinnacle.drops')->as('drops.updated')->with(['refresh' => true])->sendNow();
            } catch (Throwable $e) {
                Log::warning('Pinnodds refresh broadcast failed; feed remains connected', ['exception' => $e::class]);
            }
        };
        $cleanup = function () use ($listenerLock, &$bodies, &$feeds, &$dirty, $flush): void {
            try {
                foreach ($bodies as $body) {
                    $body->close();
                }
                foreach ($feeds as &$feed) {
                    $feed['status'] = 'disconnected';
                }
                unset($feed);
                $dirty = true;
                $flush();
            } finally {
                $listenerLock->release();
            }
        };
        register_shutdown_function($cleanup);
        $stop = static function (): void {
            exit(self::SUCCESS);
        };
        EventLoop::onSignal(SIGINT, $stop);
        EventLoop::onSignal(SIGTERM, $stop);
        EventLoop::repeat(30, function () use ($listenerLock): void {
            try {
                if ($listenerLock->refresh(120)) {
                    return;
                }
            } catch (Throwable $e) {
                Log::error('Pinnodds listener lock refresh failed', ['exception' => $e::class]);
            }
            $this->error('Pinnodds listener lost its lock.');
            exit(self::FAILURE);
        });
        EventLoop::repeat(2, $flush);
        $flush();

        $listeners = [];
        foreach (['live' => '/odds-drop', 'prematch' => '/odds-drop-prematch'] as $mode => $path) {
            $listeners[] = async(function () use ($client, $key, $mode, $path, &$pending, &$feeds, &$bodies, &$dirty): void {
                $backoff = 1;
                while (true) {
                    $retryAfter = 0;
                    $feeds[$mode]['status'] = 'connecting';
                    $dirty = true;
                    try {
                        $request = new Request(rtrim((string) config('services.pinnodds.url'), '/').$path.'?min_drop='.($mode === 'prematch' ? 1 : 5));
                        $request->setHeader('x-api-key', $key);
                        $request->setHeader('Accept', 'text/event-stream');
                        $request->setTcpConnectTimeout(5);
                        $request->setTransferTimeout(0);
                        $request->setInactivityTimeout(90);
                        $request->setBodySizeLimit(0);
                        $response = $client->request($request);
                        $bodies[$mode] = $response->getBody();
                        if ($response->getStatus() !== 200) {
                            $retryAfter = in_array($response->getStatus(), [401, 403], true) ? 300 : max(0, (int) $response->getHeader('Retry-After'));
                            throw new \RuntimeException('Pinnodds SSE returned HTTP '.$response->getStatus());
                        }
                        if (! str_starts_with(strtolower((string) $response->getHeader('Content-Type')), 'text/event-stream')) {
                            throw new \RuntimeException('Pinnodds response is not an SSE stream.');
                        }
                        $feeds[$mode] = ['status' => 'connected', 'last_frame_at' => time()];
                        $dirty = true;
                        $this->info('Connected to Pinnodds '.$mode.' drops.');
                        $buffer = '';
                        $dataLines = [];
                        $dataSize = 0;
                        while (($chunk = $bodies[$mode]->read()) !== null) {
                            $feeds[$mode]['last_frame_at'] = time();
                            $buffer .= $chunk;
                            if (strlen($buffer) > 8_000_000) {
                                throw new \RuntimeException('Pinnodds SSE frame exceeds the size limit.');
                            }
                            while (($lineEnd = strpos($buffer, "\n")) !== false) {
                                $line = rtrim(substr($buffer, 0, $lineEnd), "\r");
                                $buffer = substr($buffer, $lineEnd + 1);
                                if (str_starts_with($line, 'data:')) {
                                    $dataSize += strlen($line);
                                    if ($dataSize > 8_000_000) {
                                        throw new \RuntimeException('Pinnodds SSE event exceeds the size limit.');
                                    }
                                    $dataLines[] = ltrim(substr($line, 5), ' ');
                                } elseif ($line === '' && $dataLines !== []) {
                                    $frame = json_decode(implode("\n", $dataLines), true);
                                    $dataLines = [];
                                    $dataSize = 0;
                                    if (! is_array($frame)) {
                                        continue;
                                    }
                                    if (($frame['type'] ?? null) === 'error') {
                                        $errorCode = (string) ($frame['error'] ?? 'unknown');
                                        $retryAfter = in_array($errorCode, ['plan_lacks_sse', 'invalid_key', 'unauthorized', 'key_expired'], true) ? 300 : 60;
                                        throw new \RuntimeException('Pinnodds SSE error: '.$errorCode);
                                    }
                                    $backoff = 1;
                                    if (isset($frame['type'])) {
                                        continue;
                                    }
                                    $drops = $this->normalizeDrops($frame, $mode);
                                    if ($drops !== []) {
                                        $pending = array_replace($pending, $drops);
                                        $dirty = true;
                                    }
                                }
                            }
                        }
                        throw new \RuntimeException('Pinnodds SSE stream closed.');
                    } catch (Throwable $e) {
                        $feeds[$mode]['status'] = 'disconnected';
                        $dirty = true;
                        $this->warn('Pinnodds '.$mode.' disconnected; retrying in '.max($backoff, $retryAfter).' seconds.');
                        Log::warning('Pinnodds SSE disconnected', [
                            'mode' => $mode,
                            'exception' => $e::class,
                            'reason' => $e instanceof \RuntimeException ? str_replace($key, '[redacted]', $e->getMessage()) : 'Transport failure',
                            'retry_in_seconds' => max($backoff, $retryAfter),
                        ]);
                    } finally {
                        if (isset($bodies[$mode])) {
                            $bodies[$mode]->close();
                            unset($bodies[$mode]);
                        }
                    }
                    delay(max($backoff, $retryAfter));
                    $backoff = min($backoff * 2, 60);
                }
            });
        }
        await($listeners);

        return self::SUCCESS;
    }

    /** @return array<string, array<string, mixed>> */
    private function normalizeDrops(array $frame, string $mode): array
    {
        $alerts = [];
        $sports = [1 => 'soccer', 2 => 'tennis', 3 => 'basketball', 4 => 'ice-hockey', 5 => 'american-football', 6 => 'baseball', 11 => 'esports', 13 => 'cricket'];
        foreach (array_is_list($frame) ? $frame : [$frame] as $drop) {
            if (! is_array($drop) || empty($drop['home']) || empty($drop['away']) || ! isset($drop['id'])) {
                continue;
            }
            $from = (float) ($drop['from_price'] ?? 0);
            $to = (float) ($drop['to_price'] ?? 0);
            if (! is_finite($from) || ! is_finite($to) || $from <= 1 || $to <= 1 || round((($from - $to) / $from) * 100, 8) < ($mode === 'prematch' ? 1 : 5)) {
                continue;
            }
            $alerted = is_numeric($drop['alerted'] ?? null) ? (int) $drop['alerted'] : time();
            if ($alerted > 100_000_000_000) {
                $alerted = (int) floor($alerted / 1000);
            }
            $alertKey = hash('sha256', implode('|', [$mode, $drop['id'], $drop['period'] ?? 0, $drop['sect'] ?? '', $drop['outcome'] ?? '', $drop['point'] ?? $drop['points'] ?? $drop['hdp'] ?? '', $alerted, $from, $to]));
            $alerts[$alertKey] = array_replace($drop, [
                'alert_key' => $alertKey,
                'feed' => $mode,
                'sport' => $sports[(int) ($drop['sport_id'] ?? 0)] ?? strtolower((string) ($drop['sport'] ?? 'other')),
                'is_live' => filter_var($drop['is_live'] ?? ($mode === 'live'), FILTER_VALIDATE_BOOLEAN),
                'from_price' => $from,
                'to_price' => $to,
                'alerted' => $alerted,
                'drop_pct' => (($from - $to) / $from) * 100,
            ]);
        }

        return $alerts;
    }

    /** @param array<string, array<string, mixed>> $pending */
    private function storeDrops(array $pending, array $feeds): void
    {
        withRedisLock('Pinnacle:drops', function () use ($pending, $feeds): void {
            $cached = getInRedis('Pinnacle:drops') ?? [];
            $alerts = [];
            foreach ($cached['signals'] ?? $cached['alerts'] ?? [] as $alert) {
                $mode = $alert['feed'] ?? (($alert['is_live'] ?? false) ? 'live' : 'prematch');
                $alerts = array_replace($alerts, $this->normalizeDrops([$alert], $mode));
            }
            $alerts = array_filter(array_replace($alerts, $pending), static fn (array $alert): bool => (int) ($alert['alerted'] ?? 0) >= time() - 10800);
            uasort($alerts, static fn (array $left, array $right): int => (int) $right['alerted'] <=> (int) $left['alerted']);
            $signals = array_values(array_slice($alerts, 0, 5000, true));
            $dashboardAlerts = array_values(array_slice(array_filter($alerts, static fn (array $alert): bool => $alert['drop_pct'] >= 5), 0, 1000, true));
            Redis::set('Pinnacle:drops', json_encode(['alerts' => $dashboardAlerts, 'signals' => $signals, 'feeds' => $feeds, 'updated_at' => time()], JSON_THROW_ON_ERROR));
            Redis::expire('Pinnacle:drops', 10800);
        });
    }
}
