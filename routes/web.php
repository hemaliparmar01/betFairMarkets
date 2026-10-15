<?php

use App\Http\Controllers\Users\AmericanFootball\AmericanFootballController;
use App\Http\Controllers\Users\AmericanFootball\AmericanFootballSyncController;
use App\Http\Controllers\Users\AuthController;
use App\Http\Controllers\Users\Baseball\BaseballController;
use App\Http\Controllers\Users\Baseball\BaseballSyncController;
use App\Http\Controllers\Users\Basketball\BasketballController;
use App\Http\Controllers\Users\Basketball\BasketballSyncController;
use App\Http\Controllers\Users\ContactController;
use App\Http\Controllers\Users\Cricket\CricketController;
use App\Http\Controllers\Users\Cricket\CricketSyncController;
use App\Http\Controllers\Users\FavoriteController;
use App\Http\Controllers\Users\FeatureRequestController;
use App\Http\Controllers\Users\Football\FootballController;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Http\Controllers\Users\Greyhounds\GreyhoundsController;
use App\Http\Controllers\Users\Greyhounds\GreyhoundsSyncController;
use App\Http\Controllers\Users\HorseRacing\HorseRacingController;
use App\Http\Controllers\Users\HorseRacing\HorseRacingSyncController;
use App\Http\Controllers\Users\IceHockey\IceHockeyController;
use App\Http\Controllers\Users\IceHockey\IceHockeySyncController;
use App\Http\Controllers\Users\NotificationController;
use App\Http\Controllers\Users\StaticPageController;
use App\Http\Controllers\Users\SubscriptionController;
use App\Http\Controllers\Users\Tennis\TennisController;
use App\Http\Controllers\Users\Tennis\TennisSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('popup', function () {
    return view('popup');
});

Route::middleware('guest:customers')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::get('register', [AuthController::class, 'register'])->name('register');
    Route::get('verify-email', [AuthController::class, 'verification'])->name('customers.verification');
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:20,10')->name('customers.verification.verify');
    Route::post('verify-email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:5,60')->name('customers.verification.resend');
    Route::post('login', [AuthController::class, 'authenticate'])->name('login.store');
    Route::post('register', [AuthController::class, 'store'])->name('register.store');
    Route::post('login/logout-previous-sessions', [AuthController::class, 'confirmLogin'])->name('login.sessions.confirm');
    // Route::post('login', [AuthController::class, 'authenticate'])->middleware('throttle:30,1')->name('login.store');
    // Route::post('register', [AuthController::class, 'store'])->middleware('throttle:5,60')->name('register.store');
    // Route::post('login/logout-previous-sessions', [AuthController::class, 'confirmLogin'])->middleware('throttle:10,1')->name('login.sessions.confirm');
    Route::post('login/cancel', [AuthController::class, 'cancelLogin'])->name('login.sessions.cancel');
});
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:customers')->name('logout');
Route::get('my-profile', [AuthController::class, 'profile'])->middleware('auth:customers')->name('customers.profile');
Route::put('my-profile', [AuthController::class, 'updateProfile'])->middleware('auth:customers')->name('customers.profile.update');
Route::get('my-profile/avatar', [AuthController::class, 'avatar'])->middleware('auth:customers')->name('customers.avatar');

Route::get('temp-data-merge', [FootballController::class, 'tempDataMerge']);

Route::get('/', [FootballController::class, 'football'])->name('football.home');
Route::post('favorites/toggle', [FavoriteController::class, 'toggle'])->middleware('auth:customers')->name('favorites.toggle');
Route::prefix('football')->name('football.')->group(function () {
    Route::get('/matches/{matchId}', [FootballController::class, 'matchDetail'])->name('matches.show');
    Route::get('/market-activity', [FootballController::class, 'marketActivity'])->name('market-activity');
    Route::get('/pinnacle-odds', [FootballController::class, 'marketActivity'])->name('pinnacle-odds');
    Route::post('filter-football', [FootballController::class, 'filterFootball'])->name('filter-football');
    Route::get('sync/betfair/live-feed', [FootballSyncController::class, 'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed', [FootballSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed', [FootballSyncController::class, 'syncSportsApiProLiveData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/prematch-feed', [FootballSyncController::class, 'syncSportsApiProPrematchData'])->name('sync.sports-api-pro.prematch');
    Route::post('websocket-football', [FootballController::class, 'websocketFootball']);
    Route::get('get-cache-store-data', [FootballSyncController::class, 'getCacheStoreData']);
});

Route::prefix('cricket')->name('cricket.')->group(function () {
    Route::get('/', [CricketController::class, 'cricket'])->name('home');
    Route::get('matches/{matchId}', [CricketController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [CricketController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-cricket', [CricketController::class, 'filterCricket'])->name('filter-cricket');
    Route::post('websocket-cricket', [CricketController::class, 'websocketCricket'])->name('websocket-cricket');
    Route::get('sync/betfair/live-feed', [CricketSyncController::class, 'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed', [CricketSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed', [CricketSyncController::class, 'syncSportsApiProLiveData'])->name('sync.sports-api-pro.live');
    Route::get('sync/sportsApiPro/prematch-feed', [CricketSyncController::class, 'syncSportsApiProPrematchData'])->name('sync.sports-api-pro.prematch');
});

Route::prefix('icehockey')->name('icehockey.')->group(function () {
    Route::get('/', [IceHockeyController::class, 'icehockey'])->name('home');
    Route::get('matches/{matchId}', [IceHockeyController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [IceHockeyController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-icehockey', [IceHockeyController::class, 'filterIceHockey'])->name('filter-icehockey');
    Route::post('websocket-icehockey', [IceHockeyController::class, 'websocketIceHockey'])->name('websocket-icehockey');
    Route::get('sync/betfair/live-feed', [IceHockeySyncController::class, 'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed', [IceHockeySyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed', [IceHockeySyncController::class, 'syncSportsApiProLiveData'])->name('sync.sports-api-pro.live');
    Route::get('sync/sportsApiPro/prematch-feed', [IceHockeySyncController::class, 'syncSportsApiProPrematchData'])->name('sync.sports-api-pro.prematch');
});

Route::prefix('americanfootball')->name('americanfootball.')->group(function () {
    Route::get('/', [AmericanFootballController::class, 'americanfootball'])->name('home');
    Route::get('matches/{matchId}', [AmericanFootballController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [AmericanFootballController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-americanfootball', [AmericanFootballController::class, 'filterAmericanFootball'])->name('filter-americanfootball');
    Route::post('websocket-americanfootball', [AmericanFootballController::class, 'websocketAmericanFootball'])->name('websocket-americanfootball');
    Route::get('sync/betfair/live-feed', [AmericanFootballSyncController::class, 'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed', [AmericanFootballSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed', [AmericanFootballSyncController::class, 'syncSportsApiProLiveData'])->name('sync.sports-api-pro.live');
    Route::get('sync/sportsApiPro/prematch-feed', [AmericanFootballSyncController::class, 'syncSportsApiProPrematchData'])->name('sync.sports-api-pro.prematch');
});

Route::prefix('basketball')->name('basketball.')->group(function () {
    Route::get('/', [BasketballController::class, 'basketball'])->name('home');
    Route::get('matches/{matchId}', [BasketballController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [BasketballController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-basketball', [BasketballController::class, 'filterBasketball'])->name('filter-basketball');
    Route::post('websocket-basketball', [BasketballController::class, 'websocketBasketball'])->name('websocket-basketball');
    Route::get('sync/betfair/live-feed', [BasketballSyncController::class, 'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed', [BasketballSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed', [BasketballSyncController::class, 'syncSportsApiProLiveData'])->name('sync.sports-api-pro.live');
    Route::get('sync/sportsApiPro/prematch-feed', [BasketballSyncController::class, 'syncSportsApiProPrematchData'])->name('sync.sports-api-pro.prematch');
});

Route::prefix('tennis')->name('tennis.')->group(function () {
    Route::get('/', [TennisController::class, 'tennis'])->name('home');
    Route::get('matches/{matchId}', [TennisController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [TennisController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-tennis', [TennisController::class, 'filterTennis'])->name('filter-tennis');
    Route::post('websocket-tennis', [TennisController::class, 'websocketTennis'])->name('websocket-tennis');
    Route::get('sync/betfair/live-feed', [TennisSyncController::class, 'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed', [TennisSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed', [TennisSyncController::class, 'syncSportsApiProLiveData'])->name('sync.sports-api-pro.live');
    Route::get('sync/sportsApiPro/prematch-feed', [TennisSyncController::class, 'syncSportsApiProPrematchData'])->name('sync.sports-api-pro.prematch');
});

Route::prefix('greyhounds')->name('greyhounds.')->group(function () {
    Route::get('/', [GreyhoundsController::class, 'greyhounds'])->name('home');
    Route::get('matches/{matchId}', [GreyhoundsController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [GreyhoundsController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-greyhounds', [GreyhoundsController::class, 'filterGreyhounds'])->name('filter-greyhounds');
    Route::post('websocket-greyhounds', [GreyhoundsController::class, 'websocketGreyhounds'])->name('websocket-greyhounds');
    Route::get('sync/betfair/prematch-feed', [GreyhoundsSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
});

Route::prefix('horse-racing')->name('horseracing.')->group(function () {
    Route::get('/sportsApiPro/live-feed', [HorseRacingSyncController::class, 'syncSportsApiProLiveData'])->middleware('auth:customers')->name('sportsApiPro.live.data');
    Route::get('/sportsApiPro/prematch-feed', [HorseRacingSyncController::class, 'syncSportsApiProPrematchData'])->middleware('auth:customers')->name('sportsApiPro.prematch.data');
    Route::get('/', [HorseRacingController::class, 'horseracing'])->name('home');
    Route::get('matches/{matchId}', [HorseRacingController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [HorseRacingController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-horseracing', [HorseRacingController::class, 'filterHorseRacing'])->name('filter-horseracing');
    Route::post('websocket-horseracing', [HorseRacingController::class, 'websocketHorseRacing'])->name('websocket-horseracing');
    Route::get('sync/betfair/prematch-feed', [HorseRacingSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
});

Route::prefix('baseball')->name('baseball.')->group(function () {
    Route::get('/', [BaseballController::class, 'baseball'])->name('home');
    Route::get('matches/{matchId}', [BaseballController::class, 'matchDetail'])->name('matches.show');
    Route::get('market-activity', [BaseballController::class, 'marketActivity'])->name('market-activity');
    Route::post('filter-baseball', [BaseballController::class, 'filterBaseball'])->name('filter-baseball');
    Route::post('websocket-baseball', [BaseballController::class, 'websocketBaseball'])->name('websocket-baseball');
    Route::get('sync/betfair/live-feed', [BaseballSyncController::class, 'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed', [BaseballSyncController::class, 'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed', [BaseballSyncController::class, 'syncSportsApiProLiveData'])->name('sync.sports-api-pro.live');
    Route::get('sync/sportsApiPro/prematch-feed', [BaseballSyncController::class, 'syncSportsApiProPrematchData'])->name('sync.sports-api-pro.prematch');
});

Route::get('delete-cache-key/{forgot_cache_key}', function (Request $request) {
    $cacheKey = $request->forgot_cache_key;

    return Redis::del($cacheKey);
});

Route::get('contact-us', [ContactController::class, 'contact'])->name('contact-us');
Route::get('notifications', [NotificationController::class, 'notifications'])->name('notifications');
Route::get('subscriptions', [SubscriptionController::class, 'subscriptions'])->name('subscriptions');
Route::get('subscriptions-details', [SubscriptionController::class, 'getSubscriptions'])->name('checkout-single');
Route::get('feature-request', [FeatureRequestController::class, 'featureRequest'])->name('feature-request');
Route::get('disclaimer', [StaticPageController::class, 'disclaimer'])->name('disclaimer');
Route::get('privacy-policy', [StaticPageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('terms-of-service', [StaticPageController::class, 'termsOfService'])->name('terms-of-service');
Route::get('refund-policy', [StaticPageController::class, 'refundPolicy'])->name('refund-policy');
Route::get('faq', [StaticPageController::class, 'faq'])->name('faq');
Route::get('manifesto', [StaticPageController::class, 'manifesto'])->name('manifesto');
Route::get('filter-guide', [StaticPageController::class, 'filterGuide'])->name('filter-guide');
Route::get('market-activity-guide', [StaticPageController::class, 'marketActivityGuide'])->name('market-activity-guide');
