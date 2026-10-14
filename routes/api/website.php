<?php

use App\Http\Controllers\Api\Website\HomeController;
use App\Http\Controllers\Api\Website\ProfileController;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Facades\RedotAuth;

/*
|--------------------------------------------------------------------------
| Website API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your website.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('index');

Route::middleware('auth:users-api')->group(function () {
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register auth routes for your website.
|
*/

RedotAuth::routes(guard: 'users-api');
