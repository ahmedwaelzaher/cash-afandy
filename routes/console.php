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

use App\Jobs\SendScheduledWebsiteNotifications;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SendScheduledWebsiteNotifications)->everyMinute();

Schedule::command('queue:work --tries=3 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue.log'));
