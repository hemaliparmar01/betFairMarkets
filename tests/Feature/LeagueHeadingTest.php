<?php

namespace Tests\Feature;

use Tests\TestCase;

class LeagueHeadingTest extends TestCase
{
    public function test_provider_country_distinguishes_identically_named_leagues(): void
    {
        $france = ['tournament_name' => 'Ligue 2', 'tournament_country' => 'France', 'country_code' => 'FR'];
        $tunisia = ['tournament_name' => 'Ligue 1', 'country' => ['name' => 'Tunisia', 'alpha2' => 'TN']];
        foreach ([$france, $tunisia] as $match) {
            $heading = array_merge($match, leagueHeadingMetadata($match));
            $html = view('users.layout.leagueHeading', compact('heading'))->render();
            $this->assertStringContainsString($heading['country_name'].' - '.$match['tournament_name'], $html);
            $this->assertStringContainsString('league-heading-flag', $html);
        }
        $this->assertNotSame(leagueHeadingKey(['country_id' => 1], 'Ligue 1'), leagueHeadingKey(['country_id' => 2], 'Ligue 1'));
    }

    public function test_missing_country_does_not_infer_it_from_the_league(): void
    {
        $heading = ['tournament_name' => 'Ligue 1'];
        $html = view('users.layout.leagueHeading', compact('heading'))->render();
        $this->assertStringContainsString('Ligue 1', $html);
        $this->assertStringNotContainsString('France', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertNull(leagueHeadingMetadata(['country_id' => 42])['country_name']);
        $this->assertNull(leagueHeadingMetadata(['country_code' => '123'])['country_code']);
    }

    public function test_existing_images_are_reused_without_duplicate_or_failed_image_placeholders(): void
    {
        $heading = ['tournament_name' => 'League', 'country_name' => 'France', 'country_flag' => '/flags/fr.svg', 'tournament_logo' => '/logos/league.png'];
        $html = view('users.layout.leagueHeading', compact('heading'))->render();
        $this->assertSame(2, substr_count($html, '<img'));
        $this->assertStringContainsString('onerror="this.remove()"', $html);
        $heading['tournament_logo'] = $heading['country_flag'];
        $this->assertSame(1, substr_count(view('users.layout.leagueHeading', compact('heading'))->render(), '<img'));
    }
}
