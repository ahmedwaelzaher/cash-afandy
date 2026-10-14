<?php

use App\Http\Controllers\Website\CashbackController;
use App\Http\Controllers\Website\CategoryController;
use App\Http\Controllers\Website\ClientController;
use App\Http\Controllers\Website\CouponController;
use App\Http\Controllers\Website\Finance\CategoryController as FinanceCategoryController;
use App\Http\Controllers\Website\HealthCheckController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\ProfileController;
use App\Http\Controllers\Website\SearchController;
use App\Http\Controllers\Website\ShortenedUrlController;
use App\Http\Controllers\Website\SocialiteController;
use App\Http\Controllers\Website\StaticPageController;
use App\Http\Controllers\Website\StoreSubscriberController;
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

Route::get('/', HomeController::class)->name('index');
Route::get('up', HealthCheckController::class)->name('health-check');
Route::get('r/{shortenedUrl?}', [ShortenedUrlController::class, 'show'])->name('shortened-urls.show');
Route::get('static-pages/{staticPage}', [StaticPageController::class, 'show'])->name('static-pages.show');
Route::post('subscribers', StoreSubscriberController::class)->name('subscribers.store');

Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index');
Route::get('coupons/{coupon}', [CouponController::class, 'show'])->name('coupons.show');

Route::get('cashbacks', [CashbackController::class, 'index'])->name('cashbacks.index');
Route::get('cashbacks/{cashback}', [CashbackController::class, 'show'])->name('cashbacks.show');

Route::get('stores/{client}', [ClientController::class, 'show'])->name('clients.show');

Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('search', SearchController::class)->name('search');

Route::middleware('guest:users')->group(function () {
    Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->whereIn('provider', SocialiteController::PROVIDERS)->name('socialite.redirect');
    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])->whereIn('provider', SocialiteController::PROVIDERS)->name('socialite.callback');
});

Route::middleware('auth:users')->group(function () {
    Route::get('profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::put('profile/complete', [ProfileController::class, 'storeCompletion'])->name('profile.complete.store');

    Route::middleware(EnsureProfileIsComplete::class)->group(function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');

        Route::prefix('finance')->name('finance.')->group(function () {
            Route::resource('categories', FinanceCategoryController::class)->except(['show']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register auth routes for your website.
|
*/

RedotAuth::routes(
    guard: 'users',
    views: [
        'login' => 'website.auth.login',
        'register' => 'website.auth.register',
        'forgot-password' => 'website.auth.forgot-password',
        'reset-password' => 'website.auth.reset-password',
        'magic-link' => 'website.auth.magic-link',
        'magic-link-code' => 'website.auth.magic-link-code',
        'verify-email' => 'website.auth.verify-email',
    ],
);
