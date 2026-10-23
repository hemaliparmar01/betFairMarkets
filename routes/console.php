<?php

use App\Http\Controllers\Users\AmericanFootball\AmericanFootballSyncController;
use App\Http\Controllers\Users\Baseball\BaseballSyncController;
use App\Http\Controllers\Users\Basketball\BasketballSyncController;
use App\Http\Controllers\Users\Cricket\CricketSyncController;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Http\Controllers\Users\Greyhounds\GreyhoundsSyncController;
use App\Http\Controllers\Users\HorseRacing\HorseRacingSyncController;
use App\Http\Controllers\Users\IceHockey\IceHockeySyncController;
use App\Http\Controllers\Users\Tennis\TennisSyncController;
use App\Services\FootballV6Stats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sports:process-match-backups')->everyFiveMinutes()->withoutOverlapping(5);

Schedule::call(function () {
    app(FootballV6Stats::class)->queueDueMatches();
})->name('football-v6-live-statistics')->everyMinute()->withoutOverlapping(2);

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

Schedule::call(function () {
    app(IceHockeySyncController::class)->syncBetFairLiveData();
})->name('icehockey-betfair-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(IceHockeySyncController::class)->syncSportsApiProLiveData();
})->name('icehockey-sportApiPro-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(IceHockeySyncController::class)->syncBetFairPrematchData();
})->name('icehockey-betfair-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(IceHockeySyncController::class)->syncSportsApiProPrematchData();
})->name('icehockey-sportApiPro-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(AmericanFootballSyncController::class)->syncBetFairLiveData();
})->name('americanfootball-betfair-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(AmericanFootballSyncController::class)->syncSportsApiProLiveData();
})->name('americanfootball-sportApiPro-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(AmericanFootballSyncController::class)->syncBetFairPrematchData();
})->name('americanfootball-betfair-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(AmericanFootballSyncController::class)->syncSportsApiProPrematchData();
})->name('americanfootball-sportApiPro-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(BaseballSyncController::class)->syncBetFairLiveData();
})->name('baseball-betfair-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(BaseballSyncController::class)->syncSportsApiProLiveData();
})->name('baseball-sportApiPro-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(BaseballSyncController::class)->syncBetFairPrematchData();
})->name('baseball-betfair-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(BaseballSyncController::class)->syncSportsApiProPrematchData();
})->name('baseball-sportApiPro-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(BasketballSyncController::class)->syncBetFairLiveData();
})->name('basketball-betfair-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(BasketballSyncController::class)->syncSportsApiProLiveData();
})->name('basketball-sportApiPro-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(BasketballSyncController::class)->syncBetFairPrematchData();
})->name('basketball-betfair-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(BasketballSyncController::class)->syncSportsApiProPrematchData();
})->name('basketball-sportApiPro-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(TennisSyncController::class)->syncBetFairLiveData();
})->name('tennis-betfair-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(TennisSyncController::class)->syncSportsApiProLiveData();
})->name('tennis-sportApiPro-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(TennisSyncController::class)->syncBetFairPrematchData();
})->name('tennis-betfair-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(TennisSyncController::class)->syncSportsApiProPrematchData();
})->name('tennis-sportApiPro-prematch')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    app(GreyhoundsSyncController::class)->syncBetFairPrematchData();
})->name('greyhounds-betfair-feed')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(HorseRacingSyncController::class)->syncBetFairPrematchData();
})->name('horseracing-betfair-feed')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(HorseRacingSyncController::class)->syncSportsApiProLiveData();
})->name('horseracing-sports-live')->everyFiveMinutes()->withoutOverlapping(2);

Schedule::call(function () {
    app(HorseRacingSyncController::class)->syncSportsApiProPrematchData();
})->name('horseracing-sports-prematch')->everyFifteenMinutes()->withoutOverlapping(2);

Schedule::command('subscriptions:process')->everyFiveMinutes()->withoutOverlapping(10);
