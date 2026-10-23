<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sports:process-match-backups')]
#[Description('Command description')]
class ProcessMatchBackups extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
