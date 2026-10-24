<?php

namespace App\Jobs;

use App\Services\FootballTimelineStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class PersistFootballTimeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(public string $matchId, public array $snapshot) {}

    public function uniqueId(): string
    {
        return $this->matchId;
    }

    public function uniqueVia(): Repository
    {
        return Cache::store('redis');
    }

    public function handle(FootballTimelineStore $store): void
    {
        try {
            $pending = Cache::store('redis')->get('Football:timeline:pending:'.$this->matchId);
        } catch (\Throwable) {
            $pending = null;
        }
        $store->persist($this->matchId, is_array($pending) && ($pending['version'] ?? 0) > $this->snapshot['version'] ? $pending : $this->snapshot);
    }
}
