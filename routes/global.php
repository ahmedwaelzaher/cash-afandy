<?php

use App\Http\Controllers\HugeRteController;
use App\Http\Controllers\SelectController;
use App\Http\Controllers\UploaderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Global Routes
|--------------------------------------------------------------------------
|
| Here is where you can register global routes for your application. These
| routes available to both the website and dashboard.
|
*/

Route::post('tinymce/upload', [HugeRteController::class, 'store'])->name('tinymce.upload');
Route::post('hugerte/upload', [HugeRteController::class, 'store'])->name('hugerte.upload');

Route::get('tomselect/search', [SelectController::class, 'index'])->name('select.search');
Route::get('tomselect/fetch', [SelectController::class, 'show'])->name('select.fetch');

Route::post('uploader/upload', [UploaderController::class, 'store'])->name('uploader.upload');
