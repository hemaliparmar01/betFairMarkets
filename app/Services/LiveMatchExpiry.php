<?php

namespace App\Services;

use App\Events\FootballSportsScoreUpdated;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;

class LiveMatchExpiry
{
    public const AWAITING_REPLAY = 'Finished - Awaiting replay';

    private const SPORTS = [
        'football' => [
            'cache_key' => 'Football:matches',
            'database_lock' => 'Football:database-write-lock',
            'model' => FootballMatch::class,
            'event' => FootballSportsScoreUpdated::class,
            'max_live_minutes' => 120,
        ],
    ];

    /** @return list<string> */
    public function supportedSports(): array
    {
        return array_keys(self::SPORTS);
    }

    /** @return array{cache: int, database: int} */
    public function expire(string $sport): array
    {
        $policy = self::SPORTS[$sport] ?? throw new InvalidArgumentException('Live match expiry is not configured for '.$sport.'.');
        $cutoff = now()->timestamp - $policy['max_live_minutes'] * 60;
        $counts = withRedisLock($policy['cache_key'], static function () use ($policy, $cutoff): array {
            $matches = getInRedis($policy['cache_key']) ?? [];
            $cacheCount = 0;
            foreach ($matches as &$match) {
                $startedAt = (int) ($match['timestamp'] ?? 0);
                if (($match['result_status'] ?? null) === 'Live' && $startedAt > 0 && $startedAt < $cutoff) {
                    $match['result_status'] = self::AWAITING_REPLAY;
                    $cacheCount++;
                }
            }
            unset($match);

            $databaseCount = Cache::store('redis')->lock($policy['database_lock'], 120)->block(5, static fn (): int => $policy['model']::query()
                ->where('result_status', 'Live')
                ->where('start_timestamp', '>', 0)
                ->where('start_timestamp', '<', $cutoff)
                ->update(['result_status' => self::AWAITING_REPLAY, 'payload->result_status' => self::AWAITING_REPLAY]));

            if ($cacheCount > 0) {
                Redis::setex($policy['cache_key'], 1800, json_encode($matches, JSON_THROW_ON_ERROR));
            }

            return ['cache' => $cacheCount, 'database' => $databaseCount];
        });

        if ($counts['cache'] > 0 || $counts['database'] > 0) {
            $eventClass = $policy['event'];
            Event::dispatch(new $eventClass);
        }

        return $counts;
    }
}
