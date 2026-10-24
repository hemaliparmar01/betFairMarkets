<?php

namespace Tests\Feature;

use App\Http\Controllers\Users\Football\FootballSyncController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class PrematchStatisticsTest extends TestCase
{
    private array $matches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matches = ['test' => ['sportsApiPro_match_id' => '123', 'result_status' => 'Prematch', 'timestamp' => time() + 3600, 'team1' => 'Home', 'team2' => 'Away']];
        Redis::shouldReceive('get')->andReturnUsing(fn (): string => json_encode($this->matches));
        Redis::shouldReceive('set')->andReturnUsing(function (string $key, string $value): void {
            $this->matches = json_decode($value, true);
        });
        Redis::shouldReceive('expire')->andReturn(true);
        Redis::shouldReceive('zrangebyscore')->andReturn([]);
        $lock = Mockery::mock();
        $lock->shouldReceive('get')->andReturn(true);
        $lock->shouldReceive('release');
        $lock->shouldReceive('block')->andReturnUsing(static fn (int $seconds, callable $callback): mixed => $callback());
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('lock')->andReturn($lock);
    }

    public function test_sections_load_on_demand_and_reopening_reuses_cached_data(): void
    {
        $sync = Mockery::mock(FootballSyncController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $paths = [];
        $sync->shouldReceive('requestStatisticsPayload')->times(6)->andReturnUsing(function (string $path, ?array &$diagnostics) use (&$paths): array {
            $paths[] = $path;
            $diagnostics = ['http_status' => 200];

            return ['data' => match (true) {
                str_contains($path, '/teams/') => ['statistics' => ['avgRating' => 7.2, 'matches' => 10, 'awardedMatches' => 0]],
                str_ends_with($path, '/pregame-form') => ['homeTeam' => ['form' => ['L', 'W', 'D']], 'awayTeam' => []],
                str_ends_with($path, '/h2h') => ['teamDuel' => ['homeWins' => 4, 'draws' => 1, 'awayWins' => 2]],
                str_ends_with($path, '/streaks') => ['general' => []],
                default => ['id' => 123, 'homeTeam' => ['id' => 11], 'awayTeam' => ['id' => 22], 'tournament' => ['id' => 33], 'season' => ['id' => 44], 'venue' => ['name' => 'Real stadium'], 'referee' => ['name' => 'Actual referee'], 'startTimestamp' => 1800000000, 'Info' => ['round' => 8]],
            }];
        });
        $loaded = $sync->loadMatchStatistics('123');
        $this->assertContains('https://api.sportsapipro.com/v2/football/teams/11/tournament/33/season/44/statistics', $paths);
        $this->assertContains('https://api.sportsapipro.com/v2/football/teams/22/tournament/33/season/44/statistics', $paths);
        $this->assertSame(0, $loaded['prematch_statistics']['home-season']['awardedMatches']);
        $this->assertSame($loaded, $sync->loadMatchStatistics('123'));
        $event = array_replace($loaded, ['status' => 'Prematch']);
        $html = view('users.sports.football.partials.statisticsContent', compact('event'))->render();
        foreach (['Real stadium', 'Actual referee', 'Team Season Statistics', '7.2', 'form-loss', 'form-win', 'form-draw', 'Average Rating (1-10)'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('Live statistics', $html);
    }

    public function test_missing_season_ids_and_partial_api_failure_do_not_block_the_modal(): void
    {
        $sync = Mockery::mock(FootballSyncController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $sync->shouldReceive('requestStatisticsPayload')->times(4)->andReturnUsing(static function (string $path, ?array &$diagnostics): array {
            $diagnostics = ['http_status' => str_ends_with($path, '/h2h') ? 429 : 200];

            return ['data' => str_ends_with($path, '/123') ? ['id' => 123] : []];
        });
        $loaded = $sync->loadMatchStatistics('123');
        $this->assertArrayNotHasKey('home-season', $loaded['statistics_requests']);
        $this->assertFalse($loaded['statistics_requests']['h2h']['available']);
        $event = array_replace($loaded, ['status' => 'Prematch']);
        $html = view('users.sports.football.partials.statisticsContent', compact('event'))->render();
        $this->assertStringContainsString('No data available', $html);
        $this->assertStringNotContainsString('undefined', $html);
        foreach (['No wins', 'Without clean sheet', 'First to concede', 'Losses in a row'] as $name) {
            $this->assertSame('negative', footballStreakClassification($name));
        }
        foreach (['Wins in a row', 'Clean sheets', 'First to score', 'Unbeaten'] as $name) {
            $this->assertSame('positive', footballStreakClassification($name));
        }
        $this->assertSame('neutral', footballStreakClassification('More than 2.5 goals'));
    }
}
