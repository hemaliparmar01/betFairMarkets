<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(\App\Http\Controllers\Users\Football\FootballSyncController::class)->syncBetFairLiveData();
})->name('football-betfair-live')->everyMinute()->withoutOverlapping();

Schedule::call(function () {
    app(\App\Http\Controllers\Users\Football\FootballSyncController::class)->syncSportsApiProLiveData();
})->name('football-sportApiPro-live')->everyMinute()->withoutOverlapping();

Schedule::call(function () {
    app(\App\Http\Controllers\Users\Football\FootballSyncController::class)->syncBetFairPrematchData();
})->name('football-betfair-prematch')->everyFiveMinutes()->withoutOverlapping();

