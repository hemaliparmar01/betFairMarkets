<?php

use App\Http\Controllers\Users\ContactController;
use App\Http\Controllers\Users\FeatureRequestController;
use App\Http\Controllers\Users\Football\FootballController;
use App\Http\Controllers\Users\Football\FootballSyncController;
use App\Http\Controllers\Users\NotificationController;
use App\Http\Controllers\Users\StaticPageController;
use App\Http\Controllers\Users\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

Route::get('temp-data-merge',[FootballController::class,'tempDataMerge']);

Route::get('/',[FootballController::class,'football'])->name('football.home');
Route::prefix('football')->name('football.')->group(function () {
    Route::get('/market-activity',[FootballController::class,'marketActivity'])->name('market-activity');
    Route::post('filter-football',[FootballController::class,'filterFootball'])->name('filter-football');
    Route::get('sync/betfair/live-feed',[FootballSyncController::class,'syncBetFairLiveData'])->name('sync.live.data');
    Route::get('sync/betfair/prematch-feed',[FootballSyncController::class,'syncBetFairPrematchData'])->name('sync.prematch.data');
    Route::get('sync/sportsApiPro/live-feed',[FootballSyncController::class,'syncSportsApiProLiveData'])->name('sync.prematch.data');
    Route::post('websocket-football',[FootballController::class,'websocketFootball']);
    Route::get('get-cache-store-data',[FootballSyncController::class,'getCacheStoreData']);
});

Route::get('delete-cache-key/{forgot_cache_key}',function(Request $request) {
    $cacheKey = $request->forgot_cache_key;
    return Redis::del($cacheKey);
});

Route::get('contact-us',[ContactController::class,'contact'])->name('contact-us');
Route::get('notifications',[NotificationController::class,'notifications'])->name('notifications');
Route::get('subscriptions',[SubscriptionController::class,'subscriptions'])->name('subscriptions');
Route::get('subscriptions-details',[SubscriptionController::class,'getSubscriptions'])->name('checkout-single');
Route::get('feature-request',[FeatureRequestController::class,'featureRequest'])->name('feature-request');
Route::get('disclaimer',[StaticPageController::class,'disclaimer'])->name('disclaimer');
Route::get('privacy-policy',[StaticPageController::class,'privacyPolicy'])->name('privacy-policy');
Route::get('terms-of-service',[StaticPageController::class,'termsOfService'])->name('terms-of-service');
Route::get('refund-policy',[StaticPageController::class,'refundPolicy'])->name('refund-policy');
Route::get('faq',[StaticPageController::class,'faq'])->name('faq');
Route::get('manifesto',[StaticPageController::class,'manifesto'])->name('manifesto');
Route::get('filter-guide',[StaticPageController::class,'filterGuide'])->name('filter-guide');
Route::get('market-activity-guide',[StaticPageController::class,'marketActivityGuide'])->name('market-activity-guide');
