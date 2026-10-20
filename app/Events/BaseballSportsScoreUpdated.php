<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class BaseballSportsScoreUpdated implements ShouldBeUnique, ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $connection = 'sports-realtime';

    public string $broadcastQueue = 'realtime-baseball';

    public int $tries = 6;

    public int $timeout = 10;

    public int $uniqueFor = 600;

    /** @var list<int> */
    public array $backoff = [5, 15, 30, 60, 120];

    public function uniqueId(): string
    {
        return self::class.'|'.$this->broadcastQueue;
    }

    public function uniqueVia(): Repository
    {
        return Cache::store('redis');
    }

    public function broadcastOn(): array
    {
        return [new Channel('sports.baseball.live')];
    }

    public function broadcastAs(): string
    {
        return 'score.updated';
    }

    public function broadcastWith(): array
    {
        return ['refresh' => true];
    }
}
