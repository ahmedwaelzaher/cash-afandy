<?php

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

use App\Models\RecurringTransaction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --tries=3 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue.log'));

Artisan::command('finance:post-recurring', function () {
    $posted = 0;

    RecurringTransaction::due()->with('user')->lazyById()->each(function (RecurringTransaction $recurring) use (&$posted) {
        $posted += $recurring->postDue();
    });

    $this->info("Posted {$posted} recurring transaction(s).");
})->purpose('Post the recurring income and expenses that are due by today');

Schedule::command('finance:post-recurring')
    ->dailyAt('00:10')
    ->withoutOverlapping();
