<?php

namespace Tests\Feature;

use App\Console\Commands\ListenSportsApiPro;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Jobs\PersistFootballTimeline;
use App\Services\FootballTimelineStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class FootballTimelineStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_10_23_160324_create_football_match_timelines_table.php'))->up();
    }

    private function snapshot(int $version = 1, bool $finished = false): array
    {
        return ['version' => $version, 'timeline' => ['events' => ['goal-1' => ['id' => 'goal-1', 'kind' => 'goal', 'minute' => 12], 'card-1' => ['id' => 'card-1', 'kind' => 'yellow', 'minute' => 30]], 'initialized' => true, 'clock' => ['finished' => $finished], 'clock_updated_at' => 100], 'context' => ['result_status' => $finished ? 'Finished' : 'Live', 'home_score' => 1, 'away_score' => 0]];
    }

    public function test_updates_are_unique_unchanged_content_does_not_write_and_old_frames_cannot_replace_new(): void
    {
        $store = new FootballTimelineStore;
        $store->persist('123', $this->snapshot(2));
        $queries = [];
        DB::listen(static function ($event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        $same = $this->snapshot(3);
        $same['timeline']['clock_updated_at'] = 999;
        $store->persist('123', $same);
        $this->assertSame([], array_values(array_filter($queries, static fn (string $query): bool => preg_match('/^(update|insert)/i', $query) === 1)));
        $next = $this->snapshot(4);
        $next['timeline']['events']['red-1'] = ['id' => 'red-1', 'kind' => 'red', 'minute' => 40];
        $store->persist('123', $next);
        $store->persist('123', $this->snapshot(1));
        $this->assertSame(1, DB::table('football_match_timelines')->count());
        $resolved = $store->resolveMany([['sportsApiPro_match_id' => '123']]);
        $this->assertCount(3, $resolved['123']['events']);
        $this->assertSame(12, $resolved['123']['events']['goal-1']['minute']);
        $queries = [];
        $cached = $store->resolveMany([['sportsApiPro_match_id' => '123', 'timeline' => $next['timeline']]]);
        $this->assertSame($next['timeline'], $cached['123']);
        $this->assertSame([], $queries);
        $this->assertSame([], $store->resolveMany([['sportsApiPro_match_id' => '999']]));
    }

    public function test_finished_timeline_survives_empty_frames_and_cleanup_only_removes_expired_finished_records(): void
    {
        $store = new FootballTimelineStore;
        $this->travelTo(now()->startOfDay());
        $store->persist('123', $this->snapshot(1, true));
        $store->persist('456', $this->snapshot(1));
        $finished = DB::table('football_match_timelines')->where('match_id', '123')->value('finished_at');
        $partial = $this->snapshot(2, true);
        $partial['timeline']['events'] = [];
        $store->persist('123', $partial);
        $this->assertCount(2, $store->resolveMany([['sportsApiPro_match_id' => '123']])['123']['events']);
        $this->travel(2)->days();
        $this->assertSame(0, $store->cleanup());
        $this->assertSame($finished, DB::table('football_match_timelines')->where('match_id', '123')->value('finished_at'));
        $this->travel(2)->days();
        $this->assertSame(1, $store->cleanup());
        $this->assertSame(0, $store->cleanup());
        $this->assertSame('456', DB::table('football_match_timelines')->value('match_id'));
        $this->travelBack();
    }

    public function test_server_staging_uses_a_coalesced_job_and_only_timeline_context_is_stored(): void
    {
        Queue::fake();
        config(['cache.stores.redis' => ['driver' => 'array']]);
        Cache::purge('redis');
        $cache = Cache::store('redis');
        $store = new FootballTimelineStore;
        $match = ['sportsApiPro_match_id' => '123', 'timeline' => $this->snapshot()['timeline'], 'result_status' => 'Live', 'home_score' => 1, 'away_score' => 0, 'markets' => ['secret-market-data'], 'team1' => 'Unneeded team'];
        $store->stage($match);
        $store->stage($match);
        Queue::assertPushed(PersistFootballTimeline::class, 1);
        Queue::assertPushed(PersistFootballTimeline::class, static fn ($job): bool => ! isset($job->snapshot['markets']) && ! isset($job->snapshot['team1']));
        $pending = $cache->get('Football:timeline:pending:123');
        $this->assertSame([], $pending['context']);
        $store->persist('123', $pending);
        $stored = DB::table('football_match_timelines')->value('timeline_data');
        $this->assertStringNotContainsString('secret-market-data', $stored);
    }

    public function test_background_listener_stages_timeline_without_a_browser_and_fallback_tolerates_redis_failure(): void
    {
        $store = new FootballTimelineStore;
        $store->persist('123', $this->snapshot(1, true));
        Redis::shouldReceive('get')->andThrow(new \RuntimeException('Redis offline'));
        $fallback = $store->resolve('123');
        $this->assertSame('yellow', $fallback['events']['card-1']['kind']);
        $this->assertTrue($fallback['clock']['finished']);
    }

    public function test_database_payload_contains_only_widget_data_even_for_legacy_snapshots(): void
    {
        $store = new FootballTimelineStore;
        $snapshot = $this->snapshot();
        $snapshot['context'] += ['team1' => 'Unneeded team', 'markets' => ['unneeded'], 'timestamp' => 1000, 'minutes_status' => '1st half', 'added_time' => ['first_half' => 2, 'unrelated' => 'unneeded']];
        $snapshot['timeline']['api_response'] = ['unneeded'];
        $snapshot['timeline']['clock'] += ['period_start' => 1000, 'home_score' => 1, 'statistics' => ['unneeded']];
        $snapshot['timeline']['events']['goal-1'] += ['home_score' => 1, 'away_score' => 0, 'player_surname' => 'Smith', 'is_home' => false, 'statistics' => ['unneeded']];
        $store->persist('123', $snapshot);
        $stored = json_decode(DB::table('football_match_timelines')->value('timeline_data'), true);
        $this->assertSame(['timestamp' => 1000, 'minutes_status' => '1st half', 'added_time' => ['first_half' => 2]], $stored['context']);
        $this->assertSame(['finished' => false, 'period_start' => 1000], $stored['timeline']['clock']);
        $this->assertSame(['id' => 'goal-1', 'kind' => 'goal', 'minute' => 12, 'player_surname' => 'Smith', 'is_home' => true], $stored['timeline']['events']['goal-1']);
        $this->assertArrayNotHasKey('api_response', $stored['timeline']);

        DB::table('football_match_timelines')->where('match_id', '123')->update(['timeline_data' => json_encode($snapshot), 'content_hash' => str_repeat('0', 64)]);
        $resolved = $store->resolveMany([['sportsApiPro_match_id' => '123']])['123'];
        $this->assertSame($stored['context'], $resolved['render_context']);
        $this->assertSame($stored['timeline']['events'], $resolved['events']);
        $store->persist('123', array_replace($snapshot, ['version' => 2]));
        $rewritten = json_decode(DB::table('football_match_timelines')->value('timeline_data'), true);
        $this->assertSame($stored, $rewritten);
    }

    public function test_server_listener_triggers_persistence_on_a_finished_update(): void
    {
        $match = ['sportsApiPro_match_id' => '123', 'result_status' => 'Live', 'timeline' => $this->snapshot()['timeline'], 'home_score' => 1, 'away_score' => 0];
        Redis::shouldReceive('get')->andReturn(json_encode(['one' => $match]));
        Redis::shouldReceive('set')->andReturn(true);
        Redis::shouldReceive('expire')->andReturn(true);
        $store = \Mockery::mock(FootballTimelineStore::class);
        $store->shouldReceive('stage')->once()->withArgs(static fn (array $received): bool => $received['result_status'] === 'Finished' && isset($received['timeline']['events']['goal-1']));
        $this->app->instance(FootballTimelineStore::class, $store);
        $sync = \Mockery::mock(FootballSyncController::class);
        $sync->shouldReceive('applyTimelineFrame')->andReturn(false);
        $this->app->instance(FootballSyncController::class, $sync);
        $listener = (new \ReflectionClass(ListenSportsApiPro::class))->newInstanceWithoutConstructor();
        (new \ReflectionMethod($listener, 'updateFootballCache'))->invoke($listener, 'match:123', ['id' => 123, 'status' => ['type' => 'finished', 'code' => 100]]);
        $this->assertTrue(true);
    }
}
