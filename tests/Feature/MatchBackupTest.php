<?php

namespace Tests\Feature;

use App\Console\Commands\ListenSportsApiPro;
use App\Events\TennisSportsScoreUpdated;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Models\FootballMatch;
use App\Services\MatchBackup;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MatchBackupTest extends TestCase
{
    private array $redis = [];

    private array $cleaned = [];

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $lock = Mockery::mock();
        $lock->shouldReceive('block')->andReturnUsing(static fn (int $wait, callable $callback): mixed => $callback());
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('lock')->andReturn($lock);
        Redis::shouldReceive('get')->andReturnUsing(fn (string $key): ?string => $this->redis[$key] ?? null);
        Redis::shouldReceive('set')->andReturnUsing(function (string $key, string $value): void {
            $this->redis[$key] = $value;
        });
        Redis::shouldReceive('setex')->andReturnUsing(function (string $key, int $ttl, string $value): void {
            $this->redis[$key] = $value;
        });
        Redis::shouldReceive('expire')->andReturn(true);
        Redis::shouldReceive('del')->andReturnUsing(function (string $key): void {
            unset($this->redis[$key]);
        });
        Redis::shouldReceive('zremrangebyscore')->andReturn(0);
        Redis::shouldReceive('zrangebyscore')->andReturnUsing(fn (string $key): array => array_keys($this->cleaned[$key] ?? []));
        Redis::shouldReceive('zadd')->andReturnUsing(function (string $key, int $score, string $member): void {
            $this->cleaned[$key][$member] = $score;
        });
    }

    private function match(array $changes = []): array
    {
        return array_replace(['unique_key' => 'match-1', 'timestamp' => time() - 7200, 'result_status' => 'Live', 'sportsApiPro_match_id' => '123', 'betfair_match_id' => '456', 'data_sync_complete' => true, 'markets' => []], $changes);
    }

    public function test_each_fixed_cutoff_is_inclusive_and_does_not_fabricate_a_result(): void
    {
        $service = new MatchBackup;
        $now = time();
        foreach (['football' => 120, 'basketball' => 180, 'icehockey' => 180, 'americanfootball' => 240, 'baseball' => 240] as $sport => $minutes) {
            $match = $this->match(['timestamp' => $now - $minutes * 60]);
            $hidden = $service->transition($match, $sport, $now);
            $this->assertTrue(MatchBackup::isHidden($hidden, $sport, $now));
            $this->assertSame('Live', $hidden['result_status']);
            $this->assertSame($hidden['internal_backup'], $service->transition($hidden, $sport, $now + 5)['internal_backup']);
            $this->assertFalse(MatchBackup::isHidden($this->match(['timestamp' => $now - $minutes * 60 + 1]), $sport, $now));
        }
    }

    public function test_legacy_artificial_finished_status_is_replaced_with_internal_backup(): void
    {
        $match = (new MatchBackup)->transition($this->match(['result_status' => 'Finished - Awaiting replay']), 'football', time());
        $this->assertSame('Live', $match['result_status']);
        $this->assertNotEmpty($match['internal_backup']);
        $this->assertTrue(MatchBackup::isHidden($match, 'football'));
    }

    public function test_finished_results_are_visible_and_clear_backup_even_after_cutoff(): void
    {
        $service = new MatchBackup;
        foreach ([time() - 60, time() - 20000] as $start) {
            $match = $service->transition($this->match(['timestamp' => $start, 'result_status' => 'Finished', 'internal_backup' => ['since' => time() - 60]]), 'football', time());
            $this->assertArrayNotHasKey('internal_backup', $match);
            $this->assertFalse(MatchBackup::isHidden($match, 'football'));
        }
    }

    public function test_overdue_live_update_cannot_restore_visibility_but_corrected_start_can(): void
    {
        $match = (new MatchBackup)->transition($this->match(), 'football', time());
        MatchBackup::observe($match, 'football', ['status' => ['type' => 'inprogress'], 'changes' => ['changeTimestamp' => 2]], 'match:123');
        $this->assertTrue(MatchBackup::isHidden($match, 'football'));
        MatchBackup::observe($match, 'football', ['status' => ['type' => 'notstarted'], 'startTimestamp' => time() + 1000, 'changes' => ['changeTimestamp' => time()]], 'match:123');
        $this->assertFalse(MatchBackup::isHidden($match, 'football'));
    }

    public function test_fresh_active_updates_resume_and_extend_visibility_until_activity_stops(): void
    {
        $service = new MatchBackup;
        foreach (['First Half', 'Second Half', 'In Progress'] as $stage) {
            $match = $service->transition($this->match(['timestamp' => time() - 8100, 'minutes_status' => $stage]), 'football', time());
            $this->assertTrue(MatchBackup::observe($match, 'football', ['status' => ['type' => 'inprogress'], 'changes' => ['changeTimestamp' => time()]], 'match:123'));
            $this->assertFalse(MatchBackup::isHidden($match, 'football'));
            $this->assertArrayNotHasKey('internal_backup', $service->transition($match, 'football', time() + 60));
            $this->assertFalse(MatchBackup::observe($match, 'football', ['status' => ['type' => 'inprogress'], 'changes' => ['changeTimestamp' => time()]], 'match:123'));
            $match['provider_lifecycle']['meaningful_at'] = time() - 301;
            $hidden = $service->transition($match, 'football', time());
            $this->assertTrue(MatchBackup::isHidden($hidden, 'football'));
            $this->assertSame('Live', $hidden['result_status']);
            MatchBackup::observe($hidden, 'football', ['status' => ['type' => 'inprogress'], 'changes' => ['changeTimestamp' => time() + 1]], 'match:123');
            $this->assertFalse(MatchBackup::isHidden($hidden, 'football'));
            $refreshed = MatchBackup::merge($this->match(), $hidden, 'football');
            $this->assertFalse(MatchBackup::isHidden($refreshed, 'football'));
        }
        $halfTime = $service->transition($this->match(['timestamp' => time() - 3600, 'minutes_status' => 'Half Time']), 'football', time());
        $this->assertSame('Live', $halfTime['result_status']);
        $this->assertFalse(MatchBackup::isHidden($halfTime, 'football'));
    }

    public function test_repeated_or_old_activity_does_not_resume_but_meaningful_score_change_does(): void
    {
        $service = new MatchBackup;
        $match = $service->transition($this->match(['home_score' => 1, 'away_score' => 0]), 'football', time());
        $event = ['status' => ['type' => 'inprogress']];
        $this->assertFalse(MatchBackup::observe($match, 'football', $event, 'match:123'));
        $this->assertFalse(MatchBackup::observe($match, 'football', $event + ['changes' => ['changeTimestamp' => time() - 3600]], 'match:123'));
        $this->assertTrue(MatchBackup::isHidden($match, 'football'));
        $before = $match;
        $match['home_score'] = 2;
        $this->assertTrue(MatchBackup::observe($match, 'football', $event, 'match:123', $before));
        $this->assertFalse(MatchBackup::isHidden($match, 'football'));
        $this->assertSame(2, $match['home_score']);
    }

    public function test_tennis_and_cricket_resume_preserve_scores_and_pending_recovery_blocks_cleanup(): void
    {
        foreach (['tennis', 'cricket'] as $sport) {
            $match = $this->match(['home_score' => 2, 'away_score' => 1, 'internal_backup' => ['since' => time() - 8 * 86400, 'checks' => 3]]);
            MatchBackup::observe($match, $sport, ['status' => ['type' => 'inprogress'], 'changes' => ['changeTimestamp' => time()]], 'match:123');
            $this->assertFalse(MatchBackup::isHidden($match, $sport));
            $this->assertSame(2, $match['home_score']);
            $this->assertSame(1, $match['away_score']);
        }
        $match = $this->match(['internal_backup' => ['since' => time() - 8 * 86400, 'checks' => 3]]);
        $this->redis['Football:matches'] = json_encode(['match-1' => $match]);
        $service = new MatchBackup;
        $this->assertTrue($service->recordRecheck('football', '123', null));
        $this->assertFalse($service->shouldClean(getInRedis('Football:matches')['match-1'], 'football', time()));
        $service->recordRecheck('football', '123', false);
        $this->assertTrue($service->shouldClean(getInRedis('Football:matches')['match-1'], 'football', time()));
    }

    public function test_duplicates_and_out_of_order_frames_cannot_restore_a_hidden_match(): void
    {
        $match = $this->match(['internal_backup' => ['since' => time()], 'provider_lifecycle' => ['sequence' => 20]]);
        foreach ([19, 20] as $sequence) {
            $this->assertFalse(MatchBackup::observe($match, 'football', ['status' => ['type' => 'inprogress'], 'startTimestamp' => time() + 500, 'changes' => ['changeTimestamp' => $sequence]], 'match:123'));
            $this->assertTrue(MatchBackup::isHidden($match, 'football'));
        }
    }

    public function test_tennis_and_cricket_use_failed_rechecks_not_elapsed_match_time(): void
    {
        $service = new MatchBackup;
        $now = time();
        foreach (['tennis', 'cricket'] as $sport) {
            $old = $this->match(['timestamp' => $now - 10 * 86400]);
            $this->assertArrayNotHasKey('internal_backup', $service->transition($old, $sport, $now));
            $stale = $old + ['provider_lifecycle' => ['received_at' => $now - 4 * 86400, 'checked_at' => $now, 'failed_checks' => 2]];
            $hidden = $service->transition($stale, $sport, $now);
            $this->assertTrue(MatchBackup::isHidden($hidden, $sport));
            $this->assertSame('Live', $hidden['result_status']);
            MatchBackup::observe($hidden, $sport, ['status' => ['type' => 'postponed']], 'match:123');
            $this->assertArrayHasKey('internal_backup', $hidden);
        }
        foreach (['T20', 'ODI', 'Test'] as $format) {
            $test = $service->transition($this->match(['format' => $format, 'timestamp' => $now - 5 * 86400]), 'cricket', $now);
            $this->assertSame('Live', $test['result_status']);
            $this->assertArrayNotHasKey('internal_backup', $test);
        }
    }

    public function test_cleanup_requires_retention_and_repeated_checks_and_protects_finished(): void
    {
        $service = new MatchBackup;
        $now = time();
        $old = $this->match(['internal_backup' => ['since' => $now - 7 * 86400, 'checks' => 3]]);
        $this->assertTrue($service->shouldClean($old, 'football', $now));
        $this->assertFalse($service->shouldClean(array_replace($old, ['result_status' => 'Finished']), 'football', $now));
        $this->assertFalse($service->shouldClean(array_replace($old, ['internal_backup' => ['since' => $now - 7 * 86400, 'checks' => 2]]), 'football', $now));
        config(['services.match_lifecycle.retention_seconds' => 8 * 86400]);
        $this->assertFalse($service->shouldClean($old, 'football', $now));
    }

    public function test_races_are_prematch_only_and_hidden_at_three_minutes(): void
    {
        foreach (['horseracing', 'greyhounds'] as $sport) {
            $this->assertFalse(MatchBackup::isHidden($this->match(['timestamp' => time() - 179, 'result_status' => 'Prematch']), $sport));
            $this->assertTrue(MatchBackup::isHidden($this->match(['timestamp' => time() - 180, 'result_status' => 'Prematch']), $sport));
            $this->assertTrue(MatchBackup::isHidden($this->match(['timestamp' => time() + 100, 'result_status' => 'Live']), $sport));
        }
    }

    public function test_cache_refresh_retains_backup_and_raw_provider_recovery_access(): void
    {
        $match = $this->match(['internal_backup' => ['since' => time(), 'checks' => 0]]);
        $this->redis['Football:matches'] = json_encode(['match-1' => $match]);
        $incoming = $this->match(['home_score' => 3]);
        storeInRedis('Football:matches', ['match-1' => $incoming]);
        $this->assertSame(3, getInRedis('Football:matches')['match-1']['home_score']);
        $this->assertNotEmpty(getInRedis('Football:matches')['match-1']['internal_backup']);
        $this->assertSame([], getVisibleSportsMatches('Football:matches'));
        (new MatchBackup)->recordRecheck('football', '123', false);
        $this->assertSame(1, getInRedis('Football:matches')['match-1']['internal_backup']['checks']);
        storeInRedis('Football:matches', ['match-1' => array_replace($incoming, ['result_status' => 'Finished'])]);
        $this->assertCount(1, getVisibleSportsMatches('Football:matches'));
        storeInRedis('Football:matches', ['match-1' => $incoming]);
        $this->assertSame('Finished', getInRedis('Football:matches')['match-1']['result_status']);
    }

    public function test_hidden_prematch_backup_remains_subscribed_for_provider_recovery(): void
    {
        $this->redis['Football:matches'] = json_encode([
            'hidden' => $this->match(['result_status' => 'Prematch', 'internal_backup' => ['since' => time()]]),
            'finished' => $this->match(['sportsApiPro_match_id' => '999', 'result_status' => 'Finished']),
        ]);
        $listener = (new \ReflectionClass(ListenSportsApiPro::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($listener, 'syncedMatchIds');
        $this->assertSame(['123' => 'backup'], $method->invoke($listener, 'Football:matches'));
    }

    public function test_queued_tennis_broadcast_omits_matches_that_have_become_hidden(): void
    {
        $this->redis['Tennis:matches'] = json_encode([
            'hidden' => $this->match(['internal_backup' => ['since' => time()]]),
            'visible' => $this->match(['sportsApiPro_match_id' => '999']),
        ]);
        $event = new TennisSportsScoreUpdated([['id' => '123'], ['id' => '999']]);
        $this->assertSame([['id' => '999']], $event->broadcastWith()['scores']);
    }

    public function test_football_websocket_final_score_recovers_backup_and_rejects_later_live_frames(): void
    {
        $controller = Mockery::mock(FootballSyncController::class);
        $controller->shouldReceive('applyTimelineFrame')->andReturn(false);
        $this->app->instance(FootballSyncController::class, $controller);
        $listener = (new \ReflectionClass(ListenSportsApiPro::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($listener, 'updateLiveMatch');
        $matches = ['match-1' => $this->match(['internal_backup' => ['since' => time()], 'home_score' => 0, 'away_score' => 0])];
        $event = ['id' => 123, 'status' => ['type' => 'finished', 'code' => 100], 'homeScore' => ['current' => 4], 'awayScore' => ['current' => 2]];
        $this->assertTrue($method->invokeArgs($listener, [&$matches, $event, 'match:123']));
        $this->assertSame('Finished', $matches['match-1']['result_status']);
        $this->assertSame(4, $matches['match-1']['home_score']);
        $this->assertSame(2, $matches['match-1']['away_score']);
        $this->assertArrayNotHasKey('internal_backup', $matches['match-1']);
        $method->invokeArgs($listener, [&$matches, ['id' => 123, 'status' => ['type' => 'inprogress', 'code' => 7], 'homeScore' => ['current' => 0]], 'match:123']);
        $this->assertSame('Finished', $matches['match-1']['result_status']);
        $this->assertSame(4, $matches['match-1']['home_score']);
    }

    public function test_existing_provider_status_mapping_finishes_a_backup_without_a_synthetic_result(): void
    {
        foreach (['Tennis', 'Cricket', 'Basketball', 'Baseball', 'IceHockey', 'AmericanFootball'] as $sport) {
            $class = 'App\\Http\\Controllers\\Users\\'.$sport.'\\'.$sport.'SyncController';
            $controller = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
            $match = $this->match(['internal_backup' => ['since' => time()], 'home_score' => 2, 'away_score' => 1]);
            $controller->applySportsApiEvent($match, ['id' => 123, 'status' => ['type' => 'finished', 'code' => 100]], 'match:123');
            $this->assertSame('Finished', $match['result_status']);
            $this->assertArrayNotHasKey('internal_backup', $match);
            $this->assertFalse(MatchBackup::isHidden($match, strtolower($sport)));
            $controller->applySportsApiEvent($match, ['id' => 123, 'status' => ['type' => 'inprogress', 'code' => 8]], 'match:123');
            $this->assertSame('Finished', $match['result_status']);
        }
    }

    public function test_scheduled_backup_persists_recovers_after_cache_loss_and_deletes_only_expired_match_data(): void
    {
        Schema::create('football_matches', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_key')->unique();
            $table->string('result_status');
            $table->string('minutes_status')->nullable();
            $table->integer('start_timestamp');
            $table->string('sports_api_pro_match_id')->nullable();
            $table->string('betfair_match_id')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
        Schema::create('football_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('football_match_unique_key');
            $table->string('market_id');
            $table->string('market_type');
            $table->json('payload');
        });
        $match = $this->match();
        FootballMatch::query()->create(['unique_key' => 'match-1', 'result_status' => 'Live', 'start_timestamp' => $match['timestamp'], 'sports_api_pro_match_id' => '123', 'payload' => $match]);
        $this->redis['Football:matches'] = json_encode(['match-1' => $match]);
        $service = new MatchBackup;
        $this->assertSame(1, $service->process('football'));
        $this->assertNotEmpty(FootballMatch::query()->first()->payload['internal_backup']);
        unset($this->redis['Football:matches']);
        $service->process('football');
        $this->assertSame('123', getInRedis('Football:matches')['match-1']['sportsApiPro_match_id']);
        $this->assertSame([], getVisibleSportsMatches('Football:matches'));
        $resumed = getInRedis('Football:matches')['match-1'];
        MatchBackup::observe($resumed, 'football', ['status' => ['type' => 'inprogress'], 'changes' => ['changeTimestamp' => time()]], 'match:123');
        $this->redis['Football:matches'] = json_encode(['match-1' => $resumed]);
        $service->process('football');
        unset($this->redis['Football:matches']);
        $service->process('football');
        $this->assertCount(1, getVisibleSportsMatches('Football:matches'));
        $this->assertNotEmpty(FootballMatch::query()->first()->payload['provider_lifecycle']['resumed_at']);
        $final = getInRedis('Football:matches')['match-1'];
        $final['result_status'] = 'Finished';
        $final['home_score'] = 4;
        $final['away_score'] = 2;
        MatchBackup::observe($final, 'football', ['status' => ['type' => 'finished']], 'match:123');
        $this->redis['Football:matches'] = json_encode(['match-1' => $final]);
        $service->process('football');
        $this->assertSame('Finished', FootballMatch::query()->first()->result_status);
        $this->assertSame(4, FootballMatch::query()->first()->payload['home_score']);
        $this->assertCount(1, getVisibleSportsMatches('Football:matches'));
        FootballMatch::query()->first()->update(['result_status' => 'Live']);
        $this->redis['Football:matches'] = json_encode(['match-1' => $match]);
        $expired = getInRedis('Football:matches')['match-1'];
        $expired['internal_backup'] = ['since' => time() - 8 * 86400, 'checks' => 3];
        $this->redis['Football:matches'] = json_encode(['match-1' => $expired]);
        $this->assertSame(1, $service->process('football'));
        $this->assertSame(0, FootballMatch::query()->count());
        $this->assertNull(getInRedis('Football:matches'));
        storeInRedis('Football:matches', ['match-1' => $match]);
        $this->assertSame([], getInRedis('Football:matches'));
    }
}
