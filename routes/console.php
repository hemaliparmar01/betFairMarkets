<?php

use App\Http\Controllers\Users\Football\FootballSyncController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(FootballSyncController::class)->syncBetFairLiveData();
})->name('football-betfair-live')->everyMinute()->withoutOverlapping(2);

Schedule::call(function () {
    app(FootballSyncController::class)->syncSportsApiProLiveData();
})->name('football-sportApiPro-live')->everyMinute()->withoutOverlapping(2);

Schedule::call(function () {
    app(FootballSyncController::class)->syncBetFairPrematchData();
})->name('football-betfair-prematch')->everyFiveMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(FootballSyncController::class)->syncSportsApiProPrematchData();
})->name('football-sportApiPro-prematch')->everyFiveMinutes()->withoutOverlapping(10);
