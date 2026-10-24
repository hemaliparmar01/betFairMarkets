<?php

namespace App\Console\Commands;

use App\Services\FootballTimelineStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanupFootballTimelines extends Command
{
    protected $signature = 'football:cleanup-timelines';

    protected $description = 'Remove football timeline backups three days after confirmed finish';

    public function handle(FootballTimelineStore $store): int
    {
        try {
            $this->info('Removed '.$store->cleanup().' expired football timelines.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Football timeline cleanup failed', ['exception' => $exception::class]);

            return self::FAILURE;
        }
    }
}
