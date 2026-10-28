<?php

use App\Http\Controllers\Website\CashbackController;
use App\Http\Controllers\Website\CategoryController;
use App\Http\Controllers\Website\FavoriteOfferController;
use App\Http\Controllers\Website\HealthCheckController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\OfferController;
use App\Http\Controllers\Website\PendingActionController;
use App\Http\Controllers\Website\ProfileController;
use App\Http\Controllers\Website\SearchController;
use App\Http\Controllers\Website\ShortenedUrlController;
use App\Http\Controllers\Website\SocialiteController;
use App\Http\Controllers\Website\StaticPageController;
use App\Http\Controllers\Website\StoreController;
use App\Http\Controllers\Website\StoreSubscriberController;
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

Route::get('/', HomeController::class)->name('index');
Route::get('up', HealthCheckController::class)->name('health-check');
Route::get('r/{shortenedUrl?}', [ShortenedUrlController::class, 'show'])->name('shortened-urls.show');
Route::get('static-pages/{staticPage}', [StaticPageController::class, 'show'])->name('static-pages.show');
Route::post('subscribers', StoreSubscriberController::class)->name('subscribers.store');

Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');

Route::get('cashbacks', [CashbackController::class, 'index'])->name('cashbacks.index');
Route::get('cashbacks/{cashback}', [CashbackController::class, 'show'])->name('cashbacks.show');

Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
Route::get('stores/{store}', [StoreController::class, 'show'])->name('stores.show');

Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('search', SearchController::class)->name('search');

Route::post('auth/pending-action', [PendingActionController::class, 'store'])->middleware('throttle:30,1')->name('pending-action.store');
Route::delete('auth/pending-action', [PendingActionController::class, 'destroy'])->middleware('throttle:30,1')->name('pending-action.destroy');

Route::middleware('guest:users')->group(function () {
    Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->whereIn('provider', SocialiteController::PROVIDERS)->name('socialite.redirect');
    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])->whereIn('provider', SocialiteController::PROVIDERS)->name('socialite.callback');
});

Route::middleware('auth:users')->group(function () {
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
