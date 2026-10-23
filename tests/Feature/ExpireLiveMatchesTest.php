<?php

namespace Tests\Feature;

use App\Console\Commands\ListenSportsApiPro;
use App\Events\FootballSportsScoreUpdated;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Models\FootballMatch;
use App\Services\LiveMatchExpiry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use ReflectionMethod;
use Tests\TestCase;

class ExpireLiveMatchesTest extends TestCase
{
    /** @var array<string, string> */
    private array $cacheValues = [];

    private int $cacheWrites = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'cache.stores.redis' => ['driver' => 'array']]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_10_01_000003_create_football_matches_table.php'))->up();
        (require database_path('migrations/2026_10_23_001134_widen_football_match_result_status.php'))->up();
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00', 'UTC'));
        Redis::shouldReceive('get')->andReturnUsing(fn (string $key): ?string => $this->cacheValues[$key] ?? null);
        Redis::shouldReceive('set')->andReturnUsing(function (string $key, string $value): bool {
            $this->cacheValues[$key] = $value;
            $this->cacheWrites++;

            return true;
        });
        Redis::shouldReceive('setex')->andReturnUsing(function (string $key, int $ttl, string $value): bool {
            $this->cacheValues[$key] = $value;
            $this->cacheWrites++;

            return true;
        });
        Redis::shouldReceive('expire')->andReturn(true);
    }

    public function test_expires_only_live_football_older_than_120_minutes_with_one_bulk_update(): void
    {
        Event::fake([FootballSportsScoreUpdated::class]);
        $matches = [];
        foreach (['old' => ['Live', -7201], 'boundary' => ['Live', -7200], 'recent' => ['Live', -7199], 'future' => ['Live', 60], 'finished' => ['Finished', -9000], 'prematch' => ['Prematch', -9000], 'invalid' => ['Live', null]] as $key => [$status, $offset]) {
            $matches[$key] = $this->insertMatch($key, $status, $offset === null ? 0 : now()->timestamp + $offset);
        }
        $this->cacheValues['Football:matches'] = json_encode($matches, JSON_THROW_ON_ERROR);
        $this->cacheValues['Cricket:matches'] = json_encode(['other' => $matches['old']], JSON_THROW_ON_ERROR);
        $cricket = $this->cacheValues['Cricket:matches'];
        DB::enableQueryLog();

        $counts = app(LiveMatchExpiry::class)->expire('football');

        $this->assertSame(['cache' => 1, 'database' => 1], $counts);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertSame(1, $this->cacheWrites);
        $expected = $matches;
        $expected['old']['result_status'] = LiveMatchExpiry::AWAITING_REPLAY;
        $this->assertSame($expected, getInRedis('Football:matches'));
        $this->assertSame($cricket, $this->cacheValues['Cricket:matches']);
        foreach ($expected as $key => $match) {
            $stored = FootballMatch::query()->where('unique_key', $key)->firstOrFail();
            $this->assertSame($match['result_status'], $stored->result_status);
            $this->assertSame($match['result_status'], $stored->payload['result_status']);
            $this->assertSame(2, $stored->payload['home_score']);
        }
        Event::assertDispatchedTimes(FootballSportsScoreUpdated::class, 1);
    }

    public function test_repeat_runs_do_not_rewrite_cache_or_broadcast_when_nothing_changes(): void
    {
        Event::fake([FootballSportsScoreUpdated::class]);
        $match = $this->insertMatch('old', 'Live', now()->timestamp - 7201);
        $this->cacheValues['Football:matches'] = json_encode(['old' => $match], JSON_THROW_ON_ERROR);
        app(LiveMatchExpiry::class)->expire('football');
        Event::fake([FootballSportsScoreUpdated::class]);
        $writes = $this->cacheWrites;

        $counts = app(LiveMatchExpiry::class)->expire('football');

        $this->assertSame(['cache' => 0, 'database' => 0], $counts);
        $this->assertSame($writes, $this->cacheWrites);
        Event::assertNotDispatched(FootballSportsScoreUpdated::class);
    }

    public function test_updates_database_payload_when_the_cache_is_empty(): void
    {
        Event::fake([FootballSportsScoreUpdated::class]);
        $this->insertMatch('database-only', 'Live', now()->timestamp - 7300);

        $counts = app(LiveMatchExpiry::class)->expire('football');

        $this->assertSame(['cache' => 0, 'database' => 1], $counts);
        $stored = FootballMatch::query()->firstOrFail();
        $this->assertSame(LiveMatchExpiry::AWAITING_REPLAY, $stored->result_status);
        $this->assertSame(LiveMatchExpiry::AWAITING_REPLAY, $stored->payload['result_status']);
        $this->assertSame(0, $this->cacheWrites);
        Event::assertDispatchedTimes(FootballSportsScoreUpdated::class, 1);
    }

    public function test_delayed_live_database_snapshot_preserves_awaiting_replay_until_a_real_finish(): void
    {
        $snapshot = $this->insertMatch('old', 'Live', now()->timestamp - 7300);
        Event::fake([FootballSportsScoreUpdated::class]);
        app(LiveMatchExpiry::class)->expire('football');
        $controller = app(FootballSyncController::class);

        $controller->persistOddsSnapshots([$snapshot]);

        $this->assertSame(LiveMatchExpiry::AWAITING_REPLAY, FootballMatch::query()->firstOrFail()->result_status);
        $snapshot['result_status'] = 'Finished';
        $controller->persistOddsSnapshots([$snapshot]);
        $stored = FootballMatch::query()->firstOrFail();
        $this->assertSame('Finished', $stored->result_status);
        $this->assertSame('Finished', $stored->payload['result_status']);
    }

    public function test_websocket_live_update_keeps_awaiting_replay_and_an_official_finish_replaces_it(): void
    {
        $match = $this->insertMatch('old', LiveMatchExpiry::AWAITING_REPLAY, now()->timestamp - 7300);
        $this->cacheValues['Football:matches'] = json_encode(['old' => $match], JSON_THROW_ON_ERROR);
        $listener = app(ListenSportsApiPro::class);
        $method = new ReflectionMethod($listener, 'updateFootballCache');

        $method->invoke($listener, 'match:900', ['id' => 900, 'status' => ['code' => 7, 'type' => 'inprogress']], 'update');

        $this->assertSame(LiveMatchExpiry::AWAITING_REPLAY, getInRedis('Football:matches')['old']['result_status']);
        $method->invoke($listener, 'match:900', ['id' => 900, 'status' => ['code' => 100, 'type' => 'finished']], 'update');
        $this->assertSame('Finished', getInRedis('Football:matches')['old']['result_status']);
    }

    public function test_rejects_sports_that_have_not_been_configured(): void
    {
        $this->artisan('sports:expire-live-matches', ['--sport' => 'cricket'])->assertExitCode(2);
        $this->assertSame(0, $this->cacheWrites);
    }

    /** @return array<string, mixed> */
    private function insertMatch(string $key, string $status, int $timestamp): array
    {
        $match = ['unique_key' => $key, 'sportsApiPro_match_id' => 900, 'team1' => 'Home', 'team2' => 'Away', 'timestamp' => $timestamp, 'result_status' => $status, 'minutes_status' => '2nd half', 'home_score' => 2, 'away_score' => 1];
        DB::table('football_matches')->insert(['unique_key' => $key, 'team1' => 'Home', 'team2' => 'Away', 'sports_api_pro_match_id' => '900', 'result_status' => $status, 'minutes_status' => '2nd half', 'start_timestamp' => $timestamp, 'payload' => json_encode($match, JSON_THROW_ON_ERROR)]);

        return $match;
    }
}
