<?php

use App\Http\Controllers\Users\Cricket\CricketSyncController;
use App\Http\Controllers\Users\Football\FootballSyncController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(FootballSyncController::class)->syncBetFairLiveData();
})->name('football-betfair-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(FootballSyncController::class)->syncSportsApiProLiveData();
})->name('football-sportApiPro-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(FootballSyncController::class)->syncBetFairPrematchData();
})->name('football-betfair-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(FootballSyncController::class)->syncSportsApiProPrematchData();
})->name('football-sportApiPro-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(CricketSyncController::class)->syncBetFairLiveData();
})->name('cricket-betfair-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(CricketSyncController::class)->syncSportsApiProLiveData();
})->name('cricket-sportApiPro-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(CricketSyncController::class)->syncBetFairPrematchData();
})->name('cricket-betfair-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(CricketSyncController::class)->syncSportsApiProPrematchData();
})->name('cricket-sportApiPro-prematch')->everyFifteenMinutes()->withoutOverlapping(10);
