<?php

namespace App\Console\Commands;

class ListenCricketBetfairOdds extends ListenFootballBetfairOdds
{
    protected $signature = 'cricket:odds-listen';

    protected $description = 'Listen to cricket odds on the shared Football and Cricket Betfair connection';
}
