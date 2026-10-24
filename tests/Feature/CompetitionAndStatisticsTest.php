<?php

namespace Tests\Feature;

use Tests\TestCase;

class CompetitionAndStatisticsTest extends TestCase
{
    public function test_shared_ranking_preserves_unknown_and_within_group_order(): void
    {
        $groups = ['unknown1' => ['tournament_name' => 'Unknown one'], 'france' => ['tournament_name' => 'Ligue 1', 'country_name' => 'France', 'matches' => [3, 1]], 'england' => ['tournament_name' => 'Premier League', 'country_name' => 'England'], 'unknown2' => ['tournament_name' => 'Unknown two']];
        $ranked = rankCompetitionGroups($groups);
        $this->assertSame(['england', 'france', 'unknown1', 'unknown2'], array_keys($ranked));
        $this->assertSame([3, 1], $ranked['france']['matches']);
        $this->assertSame(PHP_INT_MAX, competitionPriority('Ligue 1', 'Tunisia'));
        $this->assertSame(PHP_INT_MAX, competitionPriority('Premier League', 'Egypt'));
        $this->assertLessThan(competitionPriority('Austrian Bundesliga', 'Austria'), competitionPriority('Bundesliga', 'Germany'));
    }

    public function test_statistics_render_actual_form_h2h_and_streak_values_without_losing_extra_fields(): void
    {
        $event = ['status' => 'Prematch', 'team1' => 'Home', 'team2' => 'Away', 'prematch_statistics' => [
            'pregame-form' => ['homeTeam' => ['avgRating' => 7.2, 'position' => 4, 'value' => 18, 'form' => ['L', 'W', 'D']], 'awayTeam' => ['form' => ['W']]],
            'h2h' => ['teamDuel' => ['homeWins' => 4, 'draws' => 2, 'awayWins' => 1], 'managerDuel' => ['homeWins' => 3]],
            'streaks' => ['general' => [['name' => 'No wins', 'value' => '4', 'team' => 'home'], ['name' => 'Over 2.5 goals', 'value' => '9/10', 'team' => 'away']]],
        ]];
        $html = view('users.sports.football.partials.statisticsContent', compact('event'))->render();
        $this->assertStringContainsString('form-loss', $html);
        $this->assertStringContainsString('form-win', $html);
        $this->assertStringContainsString('form-draw', $html);
        $this->assertStringContainsString('7.2', $html);
        $this->assertStringContainsString('Home wins', $html);
        $this->assertStringContainsString('Draws', $html);
        $this->assertStringContainsString('Manager head-to-head', $html);
        $this->assertStringContainsString('statistics-streak-negative', $html);
        $this->assertStringContainsString('statistics-streak-neutral', $html);
        $this->assertStringContainsString('9/10', $html);
    }
}
