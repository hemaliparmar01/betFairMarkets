<?php

namespace App\Console\Commands;

use App\Services\MatchBackup;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sports:process-match-backups {--scheduled : Use scheduled sport cadence}')]
#[Description('Hide overdue matches and reconcile internal backup retention')]
class ProcessMatchBackups extends Command
{
    public function handle(MatchBackup $backups): int
    {
        foreach (array_keys($backups::SPORTS) as $sport) {
            if ($this->option('scheduled') && (int) now()->format('i') % 5 !== 0 && ! in_array($sport, ['horseracing', 'greyhounds'], true)) {
                continue;
            }
            $this->line($sport.': '.$backups->process($sport).' lifecycle changes');
        }

        return self::SUCCESS;
    }
}
