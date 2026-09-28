<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FootballSportsScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public array $payload)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [new Channel('sports.football.live')];

        // return [
        //     new PrivateChannel('channel-name'),
        // ];
    }

    public function broadcastAs(): string {
        return 'score.updated';
    }

    public function broadcastWith(): array {
        return $this->payload;
    }
}
