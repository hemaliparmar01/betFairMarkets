<?php

namespace App\Console\Commands;

use App\Services\LiveMatchExpiry;
use Illuminate\Console\Command;

class ExpireLiveMatches extends Command
{
    protected $signature = 'sports:expire-live-matches {--sport= : Limit expiry to a configured sport}';

    protected $description = 'Mark live matches that exceed the configured kickoff age as awaiting replay';

    public function handle(LiveMatchExpiry $expiry): int
    {
        $sport = $this->option('sport');
        $sports = $expiry->supportedSports();
        if ($sport !== null && ! in_array($sport, $sports, true)) {
            $this->error('Supported sports: '.implode(', ', $sports));

            return self::INVALID;
        }

        foreach ($sport === null ? $sports : [$sport] as $selectedSport) {
            $counts = $expiry->expire($selectedSport);
            $this->info($selectedSport.': '.$counts['cache'].' cached matches and '.$counts['database'].' database rows marked '.LiveMatchExpiry::AWAITING_REPLAY.'.');
        }

        return self::SUCCESS;
    }
}
