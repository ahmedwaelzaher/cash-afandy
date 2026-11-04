<?php

namespace App\Providers;

use App\Http\Middleware\EnsureProfileIsComplete;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Redot\Http\Middleware\Localization;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        config(['redot' => array_replace_recursive(config('redot', []), config('cash-afandy', []))]);

        require base_path('app/settings.php');

        $this->app->bind(Localization::class, \App\Http\Middleware\Localization::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->callAfterResolving(Kernel::class, fn ($kernel) => $kernel->appendMiddlewareToGroup('web', EnsureProfileIsComplete::class));
    }
}
