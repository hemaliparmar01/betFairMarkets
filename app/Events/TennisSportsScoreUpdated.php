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

class TennisSportsScoreUpdated implements ShouldBeUnique, ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $connection = 'sports-realtime';

    public string $broadcastQueue = 'realtime-tennis';

    public int $tries = 6;

    public int $timeout = 10;

    public int $uniqueFor = 600;

    /** @var list<int> */
    public array $backoff = [5, 15, 30, 60, 120];

    /** @param list<array{id: string, version: int, status: string, current_set: int, home: array, away: array}> $scores */
    public function __construct(public array $scores = []) {}

    public function uniqueId(): string
    {
        $scores = $this->scores ?? [];

        return self::class.'|'.$this->broadcastQueue.($scores === [] ? '' : '|'.hash('sha256', json_encode($scores, JSON_THROW_ON_ERROR)));
    }

    public function uniqueVia(): Repository
    {
        return Cache::store('redis');
    }

    public function broadcastOn(): array
    {
        return [new Channel('sports.tennis.live')];
    }

    public function broadcastAs(): string
    {
        return 'score.updated';
    }

    public function broadcastWith(): array
    {
        $scores = $this->scores ?? [];
        if ($scores !== []) {
            $visibleIds = array_fill_keys(array_map('strval', array_column(getVisibleSportsMatches('Tennis:matches'), 'sportsApiPro_match_id')), true);
            $scores = array_values(array_filter($scores, static fn (array $score): bool => isset($visibleIds[(string) $score['id']])));
        }

        return ['refresh' => true, 'scores' => $scores];
    }
}
