<?php

use App\Http\Controllers\Api\Dashboard\AdminController;
use App\Http\Controllers\Api\Dashboard\HomeController;
use App\Http\Controllers\Api\Dashboard\ProfileController;
use App\Http\Controllers\Api\Dashboard\RoleController;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Facades\RedotAuth;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your dashboard.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('index');

Route::middleware('auth:admins-api')->group(function () {
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('admins', [AdminController::class, 'store'])->name('admins.store');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register auth routes for your dashboard.
|
*/

Route::prefix('auth')->group(function () {
    RedotAuth::routes('dashboard-api');
});
