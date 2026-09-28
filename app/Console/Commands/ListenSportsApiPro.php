<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Amp\Websocket\Client\WebsocketHandshake;
use App\Events\FootballSportsScoreUpdated;
use Illuminate\Console\Command;
use Revolt\EventLoop;
use Throwable;
use Illuminate\Support\Facades\Event;
use function Amp\delay;
use function Amp\Websocket\Client\connect;

// #[Signature('app:listen-sports-api-pro')]
// #[Description('Command description')]
class ListenSportsApiPro extends Command
{
    // /**
    //  * Execute the console command.
    //  */
    // public function handle()
    // {
    //     //
    // }
    protected $signature = 'sports:listen';
    protected $description = 'Listen to SportsAPIPro football updates';

    public function handle(): int
    {
        $key = GetSportApiProKey();

        if (! $key) {
            $this->error('SPORTS_API_PRO_KEY is missing.');
            return self::FAILURE;
        }

        $backoff = 1;

        while (true) {
            $pingTimer = null;

            try {
                $handshake = (new WebsocketHandshake('wss://api.sportsapipro.com/v2/football/ws'))->withHeader('x-api-key', $key);

                $socket = connect($handshake);
                $backoff = 1;

                $socket->sendText(json_encode([
                    'action' => 'subscribe',
                    'channel' => 'live-scores',
                ], JSON_THROW_ON_ERROR));

                $this->info('Connected; subscribed to live-scores.');

                $pingTimer = EventLoop::repeat(30, function () use ($socket): void {
                    $socket->sendText(json_encode([
                        'action' => 'ping',
                        'channel' => 'live-scores',
                    ], JSON_THROW_ON_ERROR));
                });

                foreach ($socket as $message) {
                    $frame = json_decode($message->buffer(), true);

                    if (! is_array($frame)) {
                        continue;
                    }

                    $type = $frame['type'] ?? 'unknown';

                    // શરૂઆતમાં payload verify કરો; productionમાં આખો frame log ન કરવો.
                    $this->line("KAI Frame CHHEE : {$type}");
                    if (in_array($type, ['snapshot', 'update'], true)) {
                        // event(new \App\Events\FootballSportsScoreUpdated([
                        //     'data' => $frame['data'] ?? null,
                        // ]));
                        $this->line("Andr ave che?");
                        Event::dispatch(new FootballSportsScoreUpdated([
                            'data' => $frame['data'] ?? null,
                        ]));
                        $this->line(json_encode([
                            'type' => $type,
                            'channel' => $frame['channel'] ?? null,
                            'timestamp' => $frame['timestamp'] ?? null,
                            'data' => $frame['data'] ?? null,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        // $this->line(json_encode([
                        //     'type' => $type,
                        //     'channel' => $frame['channel'] ?? null,
                        //     'timestamp' => $frame['timestamp'] ?? null,
                        //     'data' => $frame['data'] ?? null,
                        // ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    } else {
                        $this->line("Frame: {$type}");
                    }
                }
            } catch (Throwable $e) {
                $this->error('WebSocket disconnected: '.$e->getMessage());
            } finally {
                if ($pingTimer !== null) {
                    EventLoop::cancel($pingTimer);
                }
            }

            delay($backoff);
            $backoff = min($backoff * 2, 30);
        }
    }
}
