<?php

namespace Tests\Feature;

use App\Console\Commands\ListenSportsApiPro;
use App\Http\Controllers\Users\Football\FootballController;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Models\Customer;
use App\Models\Favorite;
use App\Models\PredictionVote;
use App\Traits\FootballTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FixturesAuditTest extends TestCase
{
    private function football(): object
    {
        return new class
        {
            use FootballTrait;
        };
    }

    private function sync(): FootballSyncController
    {
        return (new ReflectionClass(FootballSyncController::class))->newInstanceWithoutConstructor();
    }

    private function timeline(array $match): array
    {
        $trait = $this->football();

        return (new ReflectionMethod($trait, 'footballTimeline'))->invoke($trait, $match, $match['timeline'] ?? []);
    }

    public function test_unsupported_smart_filters_return_empty_without_loading_cache_or_provider(): void
    {
        foreach (['Basketball', 'IceHockey', 'AmericanFootball', 'Baseball', 'Tennis', 'Cricket', 'HorseRacing', 'Greyhounds'] as $sport) {
            $class = 'App\\Http\\Controllers\\Users\\'.$sport.'\\'.$sport.'Controller';
            $instance = (new ReflectionClass($class))->newInstanceWithoutConstructor();
            $method = array_find(get_class_methods($instance), static fn (string $name): bool => preg_match('/^get\w+Matches$/', $name) === 1);
            foreach (['ht00', '00_70'] as $filter) {
                $this->app->instance('request', Request::create('/', 'GET', ['type' => $filter]));
                $this->assertSame([], $instance->$method());
            }
        }
    }

    public function test_live_upcoming_finished_and_guest_favorites_return_actual_matching_rows_across_sports(): void
    {
        Schema::create('prediction_votes', function (Blueprint $table): void {
            $table->id();
            $table->string('sport');
            $table->string('match_id');
            $table->string('poll');
            $table->string('value');
            $table->integer('customer_id')->nullable();
        });
        $cache = [];
        Redis::shouldReceive('get')->andReturnUsing(static function (string $key) use (&$cache): ?string {
            return $cache[$key] ?? null;
        });
        foreach (['Football', 'Basketball', 'IceHockey', 'AmericanFootball', 'Baseball', 'Tennis', 'Cricket'] as $sport) {
            $base = ['unique_key' => 'live', 'timestamp' => time() - 600, 'result_status' => 'Live', 'minutes_status' => 'Live', 'sportsApiPro_match_id' => '101', 'betfair_match_id' => '201', 'data_sync_complete' => true, 'tournament' => 'Test League', 'team1' => 'Home', 'team2' => 'Away', 'home_score' => 1, 'away_score' => 0, 'markets' => []];
            $cache[$sport.':matches'] = json_encode([
                'live' => $base,
                'upcoming' => array_replace($base, ['unique_key' => 'upcoming', 'sportsApiPro_match_id' => '102', 'timestamp' => time() + 120, 'result_status' => 'Prematch', 'minutes_status' => 'Prematch']),
                'finished' => array_replace($base, ['unique_key' => 'finished', 'sportsApiPro_match_id' => '103', 'result_status' => 'Finished']),
                'hidden' => array_replace($base, ['unique_key' => 'hidden', 'sportsApiPro_match_id' => '104', 'timestamp' => time() - 86400, 'internal_backup' => ['since' => time()]]),
            ]);
            $class = 'App\\Http\\Controllers\\Users\\'.$sport.'\\'.$sport.'Controller';
            $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
            $method = 'get'.$sport.'Matches';
            foreach (['live' => '101', 'upcoming' => '102', 'finished' => '103', 'favorites' => null] as $filter => $expectedId) {
                $request = Request::create('/', 'GET', ['type' => $filter]);
                $this->app->instance('request', $request);
                $groups = $controller->$method();
                $ids = [];
                foreach ($groups as $group) {
                    foreach ($group['matches'] as $match) {
                        $ids[] = (string) $match['sportsApiPro_match_id'];
                    }
                }
                $this->assertSame($expectedId === null ? [] : [$expectedId], $ids, $sport.' '.$filter);
            }
        }
    }

    public function test_football_smart_filters_use_real_scores_existing_thresholds_and_triggered_drop_sources(): void
    {
        Schema::create('prediction_votes', function (Blueprint $table): void {
            $table->id();
            $table->string('sport');
            $table->string('match_id');
            $table->string('poll');
            $table->string('value');
            $table->integer('customer_id')->nullable();
        });
        $customer = new Customer(['trial_ends_at' => now()->addDay()]);
        $this->app['auth']->guard('customers')->setUser($customer);
        $sync = \Mockery::mock(FootballSyncController::class);
        $sync->shouldReceive('queueTimelineBackfill')->andReturn(false);
        $this->app->instance(FootballSyncController::class, $sync);
        $base = ['unique_key' => '1', 'timestamp' => time() - 6000, 'result_status' => 'Live', 'minutes_status' => '2nd half', 'current_period_start_timestamp' => time() - 1500, 'sportsApiPro_match_id' => '1', 'betfair_match_id' => '201', 'data_sync_complete' => true, 'tournament' => 'Test League', 'team1' => 'Home', 'team2' => 'Away', 'home_score' => 0, 'away_score' => 0, 'markets' => []];
        $runner = ['runner' => 'Home', 'runnerId' => '1', 'marketType' => 'MATCH_ODDS', 'backOdds' => 1.8, 'initialBackOdds' => 2.0, 'monitoring_baseline_version' => 1, 'opening_odds_source' => 'first_observed', 'initialBackOddsTimestamp' => time() - 1000, 'prematch_drop_triggered' => true];
        $data = [
            '1' => array_replace($base, ['minutes_status' => 'Half Time']),
            '2' => array_replace($base, ['unique_key' => '2', 'sportsApiPro_match_id' => '2']),
            '3' => array_replace($base, ['unique_key' => '3', 'sportsApiPro_match_id' => '3', 'away_score' => 1, 'bet365_prematch' => ['favorite_odds' => 1.8, 'favorite_side' => 'home']]),
            '4' => array_replace($base, ['unique_key' => '4', 'sportsApiPro_match_id' => '4', 'bet365_prematch' => ['favorite_odds' => 1.87, 'favorite_side' => 'home']]),
            '5' => array_replace($base, ['unique_key' => '5', 'sportsApiPro_match_id' => '5', 'timestamp' => time() + 120, 'result_status' => 'Prematch', 'markets' => ['m' => [$runner]]]),
            '6' => array_replace($base, ['unique_key' => '6', 'sportsApiPro_match_id' => '6', 'timestamp' => time() + 120, 'result_status' => 'Prematch', 'pinnacle_market_shock_detected_at' => time()]),
            '7' => array_replace($base, ['unique_key' => '7', 'sportsApiPro_match_id' => '7', 'minutes_status' => 'Half Time', 'home_score' => null, 'away_score' => null]),
        ];
        Redis::shouldReceive('get')->andReturnUsing(static fn (string $key): ?string => $key === 'Football:matches' ? json_encode($data) : null);
        Redis::shouldReceive('mget')->andReturn([]);
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('many')->andReturn([]);
        foreach (['ht00' => ['1'], '00_70' => ['2', '4'], 'fav_losing' => ['3'], 'fav_not_winning' => ['3', '4'], 'prematch_drops' => ['5'], 'market_shock' => ['6']] as $filter => $expected) {
            $request = Request::create('/', 'GET', ['type' => $filter]);
            $this->app->instance('request', $request);
            $groups = $this->football()->getFootballMatches();
            $ids = [];
            foreach ($groups as $group) {
                foreach ($group['matches'] as $match) {
                    $ids[] = (string) $match['sportsApiPro_match_id'];
                }
            }
            sort($ids);
            $this->assertSame($expected, $ids, $filter);
            if ($filter === 'ht00') {
                $html = view('users.sports.football.footballDetails', ['data' => $groups, 'append' => false])->render();
                foreach (['livestats', 'quickstats', 'stats', 'prediction'] as $action) {
                    $this->assertTrue(str_contains($html, 'data-action="'.$action.'"'));
                }
                $this->assertTrue(str_contains($html, 'data-quick-stats-panel="1"'));
            }
        }
    }

    public function test_predictions_require_login_preserve_one_vote_and_feed_favorites_and_poll_counts(): void
    {
        Schema::create('prediction_votes', function (Blueprint $table): void {
            $table->id();
            $table->string('vote_key')->unique();
            $table->integer('customer_id');
            $table->string('sport');
            $table->string('match_id');
            $table->string('poll');
            $table->string('value');
            $table->timestamps();
        });
        Schema::create('favorites', function (Blueprint $table): void {
            $table->id();
            $table->integer('customer_id');
            $table->string('sport');
            $table->string('event_key');
        });
        $match = ['unique_key' => 'm1', 'timestamp' => time() + 120, 'result_status' => 'Prematch', 'minutes_status' => 'Prematch', 'sportsApiPro_match_id' => '123', 'betfair_match_id' => '201', 'data_sync_complete' => true, 'tournament' => 'Test League', 'team1' => 'Home', 'team2' => 'Away', 'markets' => []];
        $cache = ['Football:matches' => json_encode(['m1' => $match])];
        Redis::shouldReceive('get')->andReturnUsing(static function (string $key) use (&$cache): ?string {
            return $cache[$key] ?? null;
        });
        Redis::shouldReceive('set')->andReturnUsing(static function (string $key, string $value) use (&$cache): void {
            $cache[$key] = $value;
        });
        Redis::shouldReceive('expire')->andReturn(true);
        Redis::shouldReceive('zrangebyscore')->andReturn([]);
        $lock = \Mockery::mock();
        $lock->shouldReceive('block')->andReturnUsing(static fn (int $seconds, callable $callback): mixed => $callback());
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('lock')->andReturn($lock);
        $controller = \Mockery::mock(FootballController::class)->makePartial();
        $controller->shouldReceive('getFootballMatches')->andReturn([]);
        $customer = new Customer(['trial_ends_at' => now()->addDay()]);
        $customer->id = 7;
        $session = $this->app['session']->driver();
        $session->start();
        $requestFor = function (?Customer $user, string $value = '1') use ($session): void {
            $request = Request::create('/', 'POST', ['prediction' => ['match_id' => '123', 'poll' => '1x2', 'value' => $value]]);
            $request->setUserResolver(static fn () => $user);
            $request->setLaravelSession($session);
            $this->app->instance('request', $request);
        };
        $requestFor(null);
        try {
            $controller->filterFootball();
            $this->fail('Guest vote should fail.');
        } catch (HttpException $error) {
            $this->assertSame(401, $error->getStatusCode());
        }
        $this->app['auth']->guard('customers')->setUser($customer);
        $requestFor($customer, 'invalid');
        $controller->filterFootball();
        $this->assertSame(0, PredictionVote::query()->count());
        $requestFor($customer);
        $controller->filterFootball();
        $this->assertSame(1, PredictionVote::query()->count());
        $session->flush();
        $requestFor($customer, '2');
        $controller->filterFootball();
        $this->assertSame('1', PredictionVote::query()->value('value'));
        $this->assertSame(1, PredictionVote::query()->count());
        Favorite::query()->insert(['customer_id' => 7, 'sport' => 'football', 'event_key' => 'm1']);
        $request = Request::create('/', 'GET', ['type' => 'favorites']);
        $request->setUserResolver(static fn () => $customer);
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);
        $groups = $this->football()->getFootballMatches();
        $event = array_values($groups)[0]['matches'][0];
        $this->assertTrue($event['is_favorite']);
        $this->assertSame(1, $event['predictions']['1x2']['1']);
        $this->assertSame('1', $event['prediction_votes']['1x2']);
    }

    public function test_clock_requires_provider_phase_and_anchor_and_stops_for_halftime_and_finished(): void
    {
        $match = ['timestamp' => time() - 7000, 'result_status' => 'Live', 'minutes_status' => 'Live', 'home_score' => 0, 'away_score' => 0];
        $unknown = $this->timeline($match);
        $this->assertFalse($unknown['running']);
        $this->assertSame(0, $unknown['minute']);
        $match['minutes_status'] = '2nd half';
        $match['current_period_start_timestamp'] = time() - 20 * 60;
        $active = $this->timeline($match);
        $this->assertTrue($active['running']);
        $this->assertSame(65, $active['minute']);
        $stale = array_replace($match, ['minutes_status' => '1st half', 'current_period_start_timestamp' => time() - 5400]);
        $this->assertSame('45+', $this->timeline($stale)['minute_label']);
        $this->assertSame(45, $this->timeline($stale)['minute']);
        $this->assertFalse($this->timeline($stale)['running']);
        $match['minutes_status'] = 'Half Time';
        $this->assertFalse($this->timeline($match)['running']);
        $this->assertSame(45, $this->timeline($match)['minute']);
        $match['result_status'] = 'Finished';
        $finished = $this->timeline($match);
        $this->assertFalse($finished['running']);
        $this->assertSame('FT', $finished['minute_label']);
    }

    public function test_football_normalization_requires_provider_confirmation_not_finished_display_text(): void
    {
        $matches = [
            'text-only' => ['result_status' => 'Live', 'minutes_status' => 'Finished'],
            'confirmed' => ['result_status' => 'Live', 'minutes_status' => 'Live', 'internal_backup' => ['since' => time()], 'timeline' => ['clock' => ['finished' => true, 'status_code' => 'FT']]],
        ];
        Redis::shouldReceive('get')->andReturnUsing(static function () use (&$matches): string {
            return json_encode($matches);
        });
        Redis::shouldReceive('set')->andReturnUsing(static function (string $key, string $value) use (&$matches): void {
            $matches = json_decode($value, true);
        });
        Redis::shouldReceive('expire')->andReturn(true);
        $listener = (new ReflectionClass(ListenSportsApiPro::class))->newInstanceWithoutConstructor();
        (new ReflectionMethod($listener, 'updateFootballCache'))->invoke($listener, 'unrelated', []);
        $this->assertSame('Live', $matches['text-only']['result_status']);
        $this->assertSame('Live', $matches['text-only']['minutes_status']);
        $this->assertSame('Finished', $matches['confirmed']['result_status']);
        $this->assertArrayNotHasKey('internal_backup', $matches['confirmed']);
    }

    public function test_interruption_freezes_the_timeline_at_provider_pause(): void
    {
        $now = time();
        $match = ['timestamp' => $now - 6000, 'result_status' => 'Live', 'minutes_status' => '2nd half', 'home_score' => 0, 'away_score' => 0,
            'timeline' => ['clock' => ['status_code' => 7, 'status_type' => 'interrupted', 'period_start' => $now - 1800, 'initial' => 2700, 'paused_at' => $now - 600]]];
        $timeline = $this->timeline($match);
        $this->assertSame(65, $timeline['minute']);
        $this->assertFalse($timeline['running']);
    }

    public function test_missing_card_statistics_are_not_zero_but_explicit_zero_is_preserved(): void
    {
        $sync = $this->sync();
        $match = ['timeline' => ['events' => [], 'initialized' => false]];
        $sync->syncTimelineCards($match);
        $this->assertNull($match['card_counts']['home']['yellow']);
        $this->assertNull($match['card_counts']['away']['total_red']);
        $match['card_stats'] = ['yellow_cards' => ['home' => 0, 'away' => 2], 'red_cards' => ['home' => 1, 'away' => 0]];
        $sync->syncTimelineCards($match);
        $this->assertSame(0, $match['card_counts']['home']['yellow']);
        $this->assertSame(2, $match['card_counts']['away']['yellow']);
        $this->assertSame(1, $match['card_counts']['home']['total_red']);
        $this->assertSame(0, $match['card_counts']['away']['total_red']);
    }

    public function test_statistics_update_correct_team_and_reject_out_of_order_payloads(): void
    {
        $match = [];
        $sync = $this->sync();
        $payload = ['changes' => ['changeTimestamp' => 20], 'statistics' => [
            ['key' => 'yellowCards', 'home' => 1, 'away' => 3],
            ['key' => 'redCards', 'home' => 0, 'away' => 1],
            ['key' => 'cornerKicks', 'home' => 4, 'away' => 6],
        ]];
        $this->assertTrue($sync->applyMatchStatistics($match, $payload));
        $this->assertSame(['home' => 4, 'away' => 6], $match['corners']);
        $this->assertSame(1, $match['card_counts']['home']['yellow']);
        $this->assertSame(3, $match['card_counts']['away']['yellow']);
        $payload['changes']['changeTimestamp'] = 19;
        $this->assertFalse($sync->applyMatchStatistics($match, $payload));
    }

    public function test_incident_snapshots_preserve_known_goal_minutes_and_do_not_resurrect_deleted_goals(): void
    {
        $sync = $this->sync();
        $match = ['sportsApiPro_match_id' => '123', 'result_status' => 'Live'];
        $goal = ['id' => 'g1', 'incidentType' => 'goal', 'isHome' => true, 'time' => 14, 'homeScore' => 1, 'awayScore' => 0];
        $this->assertTrue($sync->applyTimelineFrame($match, 'match:123:incidents', ['incidents' => [$goal], 'changes' => ['changeTimestamp' => 10]], 'snapshot'));
        $second = array_replace($goal, ['id' => 'g2', 'time' => 60, 'homeScore' => 2]);
        $sync->applyTimelineFrame($match, 'match:123:incidents', ['incidents' => [$second], 'changes' => ['changeTimestamp' => 11]], 'snapshot');
        $this->assertCount(2, $match['timeline']['events']);
        $this->assertSame(14, $match['timeline']['events']['g1']['minute']);
        $this->assertFalse($sync->applyTimelineFrame($match, 'match:123:incidents', ['incidents' => [$goal], 'changes' => ['changeTimestamp' => 9]], 'update'));
        $sync->applyTimelineFrame($match, 'match:123:incidents', ['incidents' => [['id' => 'g1', 'isDeleted' => true]], 'changes' => ['changeTimestamp' => 12]], 'update');
        $sync->applyTimelineFrame($match, 'match:123:incidents', ['incidents' => [$goal, $second], 'changes' => ['changeTimestamp' => 13]], 'snapshot');
        $this->assertArrayNotHasKey('g1', $match['timeline']['events']);
        $this->assertArrayHasKey('g2', $match['timeline']['events']);
    }

    public function test_goal_markers_match_score_deduplicate_and_follow_credited_team_score(): void
    {
        $goal = ['id' => 'goal-1', 'kind' => 'goal', 'minute' => 14, 'added_time' => 0, 'seconds' => 840, 'is_home' => false, 'home_score' => 1, 'away_score' => 0];
        $match = ['timestamp' => time() - 3600, 'result_status' => 'Live', 'minutes_status' => '2nd half', 'home_score' => 4, 'away_score' => 2, 'timeline' => ['events' => ['goal-1' => $goal]]];
        $timeline = $this->timeline($match);
        $goals = array_values(array_filter($timeline['events'], static fn (array $event): bool => $event['kind'] === 'goal'));
        $this->assertCount(6, $goals);
        $this->assertTrue($goals[0]['is_home']);
        $this->assertSame(14, $goals[0]['minute']);
        $this->assertCount(4, array_filter($goals, static fn (array $event): bool => $event['is_home']));
        $match['away_score'] = 1;
        $this->assertCount(5, array_filter($this->timeline($match)['events'], static fn (array $event): bool => $event['kind'] === 'goal'));
    }
}
