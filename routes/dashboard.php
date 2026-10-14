<?php

use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\Dashboard\AdminImpersonateController;
use App\Http\Controllers\Dashboard\AdminNotificationController;
use App\Http\Controllers\Dashboard\BrokerController;
use App\Http\Controllers\Dashboard\CashbackController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\CouponController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ExtractLanguageTokensController;
use App\Http\Controllers\Dashboard\LanguageController;
use App\Http\Controllers\Dashboard\LanguageTokenController;
use App\Http\Controllers\Dashboard\MemoController;
use App\Http\Controllers\Dashboard\NewsController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\PostController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\PublishLanguageTokensController;
use App\Http\Controllers\Dashboard\QrCodeController;
use App\Http\Controllers\Dashboard\RepresentativeController;
use App\Http\Controllers\Dashboard\RevertLanguageTokensController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\ShortenedUrlController;
use App\Http\Controllers\Dashboard\SliderController;
use App\Http\Controllers\Dashboard\StaticPageController;
use App\Http\Controllers\Dashboard\SubscriberController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\UserImpersonateController;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Facades\RedotAuth;
use Redot\Http\Middleware\RoutePermission;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register dashboard routes for your application.
|
*/

Route::middleware('auth:admins')->group(function () {
    Route::get('/', DashboardController::class)->name('index')->withoutMiddleware(RoutePermission::class);

    /* --------- Website Management --------- */
    Route::resource('users', UserController::class)->withTrashed();
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();

    if (config('redot.features.website.enabled')) {
        Route::resource('static-pages', StaticPageController::class)->except(['show']);
    }

    Route::resource('subscribers', SubscriberController::class)->only(['index', 'destroy']);

    /* --------- Cash Afandy --------- */
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('clients', ClientController::class)->except(['show']);
    Route::resource('brokers', BrokerController::class)->except(['show']);
    Route::resource('brokers.representatives', RepresentativeController::class)->except(['show']);
    Route::resource('coupons', CouponController::class)->except(['show']);
    Route::resource('cashbacks', CashbackController::class)->except(['show']);
    Route::resource('news', NewsController::class)->except(['show']);
    Route::resource('posts', PostController::class)->except(['show']);
    Route::resource('sliders', SliderController::class)->except(['show']);

    /* --------- Utilities --------- */
    Route::withoutMiddleware(RoutePermission::class)->group(function () {
        if (config('redot.features.website.enabled')) {
            Route::resource('shortened-urls', ShortenedUrlController::class)->except(['show']);
        }

        Route::resource('memos', MemoController::class);
        Route::resource('qr-code', QrCodeController::class)->only(['index']);
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/{notification}', [NotificationController::class, 'visit'])->name('notifications.visit');
    });

    Route::get('impersonate/admins', [AdminImpersonateController::class, 'create'])->name('impersonate-admins.create');
    Route::post('impersonate/admins', [AdminImpersonateController::class, 'store'])->name('impersonate-admins.store');
    Route::get('impersonate/users', [UserImpersonateController::class, 'create'])->name('impersonate-users.create');
    Route::post('impersonate/users', [UserImpersonateController::class, 'store'])->name('impersonate-users.store');

    Route::resource('admin-notifications', AdminNotificationController::class)->only(['create', 'store']);

    /* --------- Settings --------- */
    Route::withoutMiddleware(RoutePermission::class)->group(function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('admins', AdminController::class)->except(['show']);

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::resource('languages', LanguageController::class)->except(['show']);
    Route::resource('languages.tokens', LanguageTokenController::class)->only(['index', 'edit', 'update']);
    Route::get('languages/{language}/tokens/extract', ExtractLanguageTokensController::class)->name('languages.tokens.extract');
    Route::get('languages/{language}/tokens/publish', PublishLanguageTokensController::class)->name('languages.tokens.publish');
    Route::get('languages/{language}/tokens/revert', RevertLanguageTokensController::class)->name('languages.tokens.revert');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register auth routes for your dashboard.
|
*/

Route::withoutMiddleware(RoutePermission::class)->group(function () {
    RedotAuth::routes(
        guard: 'admins',
        scope: fn ($query) => $query->where('active', true),
        views: [
            'login' => 'dashboard.auth.login',
            'forgot-password' => 'dashboard.auth.forgot-password',
            'reset-password' => 'dashboard.auth.reset-password',
            'magic-link' => 'dashboard.auth.magic-link',
            'magic-link-code' => 'dashboard.auth.magic-link-code',
            'unlock' => 'dashboard.auth.unlock',
        ],
        disable: [
            'register',
            'email-verification',
        ],
    );
});
