<?php

use App\Http\Controllers\Website\CashbackController;
use App\Http\Controllers\Website\CategoryController;
use App\Http\Controllers\Website\FavoriteOfferController;
use App\Http\Controllers\Website\HealthCheckController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\InvitationController;
use App\Http\Controllers\Website\NotificationController;
use App\Http\Controllers\Website\OfferController;
use App\Http\Controllers\Website\PendingActionController;
use App\Http\Controllers\Website\ProfileController;
use App\Http\Controllers\Website\PushSubscriptionController;
use App\Http\Controllers\Website\SearchController;
use App\Http\Controllers\Website\ShortenedUrlController;
use App\Http\Controllers\Website\SocialiteController;
use App\Http\Controllers\Website\StaticPageController;
use App\Http\Controllers\Website\StoreController;
use App\Http\Controllers\Website\StoreSubscriberController;
use App\Http\Controllers\Website\WalletController;
use App\Http\Middleware\EnsureProfileIsComplete;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Facades\RedotAuth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

Route::get('up', HealthCheckController::class)->name('health-check');
Route::get('static-pages/{staticPage}', [StaticPageController::class, 'show'])->name('static-pages.show');
Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:30,1')->name('push-subscriptions.store');
Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:30,1')->name('push-subscriptions.destroy');

Route::middleware(EnsureProfileIsComplete::class)->group(function () {
    Route::get('/', HomeController::class)->name('index');
    Route::post('auth/referral', InvitationController::class)->middleware('throttle:30,1')->name('referral.store');
    Route::get('r/{shortenedUrl?}', [ShortenedUrlController::class, 'show'])->name('shortened-urls.show');
    Route::post('subscribers', StoreSubscriberController::class)->name('subscribers.store');

    Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');

    Route::get('cashbacks', [CashbackController::class, 'index'])->name('cashbacks.index');
    Route::get('cashbacks/{cashback}', [CashbackController::class, 'show'])->name('cashbacks.show');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('stores/{store}', [StoreController::class, 'show'])->name('stores.show');

    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('search', SearchController::class)->name('search');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::post('auth/pending-action', [PendingActionController::class, 'store'])->middleware('throttle:30,1')->name('pending-action.store');
    Route::delete('auth/pending-action', [PendingActionController::class, 'destroy'])->middleware('throttle:30,1')->name('pending-action.destroy');
});

Route::middleware('guest:users')->group(function () {
    Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->whereIn('provider', SocialiteController::PROVIDERS)->name('socialite.redirect');
    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])->whereIn('provider', SocialiteController::PROVIDERS)->name('socialite.callback');
});

Route::middleware('auth:users')->group(function () {
    Route::get('profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::put('profile/complete', [ProfileController::class, 'storeCompletion'])->name('profile.complete.store');
});

Route::middleware(['auth:users', EnsureProfileIsComplete::class])->group(function () {
    Route::get('wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::post('wallet/payouts', [WalletController::class, 'storePayout'])->middleware('throttle:10,1')->name('wallet.payouts.store');

    Route::get('favorites', [FavoriteOfferController::class, 'index'])->name('favorites.index');
    Route::put('favorites/{offer}', [FavoriteOfferController::class, 'store'])->name('favorites.store');
    Route::delete('favorites/{offer}', [FavoriteOfferController::class, 'destroy'])->name('favorites.destroy');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register auth routes for your website.
|
*/

RedotAuth::routes('website');
