<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Redot\Auth\Actions\Registration;
use Redot\Auth\AuthContext;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->overrideRedotConfig();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuth();
    }

    /**
     * Make config/cash-afandy.php values always take precedence over
     * config/redot.php, since every part of the app reads via `redot.*`.
     */
    protected function overrideRedotConfig(): void
    {
        config(['redot' => array_replace_recursive(
            config('redot', []),
            config('cash-afandy', [])
        )]);
    }

    /**
     * Configure the authentication.
     */
    protected function configureAuth(): void
    {
        // Configure user registration validation rules
        Registration::validationRules('users', fn (AuthContext $context) => [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . $context->model],
            'password' => ['required', 'confirmed', Password::defaults()],
            ...User::personalDetailsRules(request('country_id')),
            ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
        ]);

        // Configure user creation
        Registration::createUserUsing('users', fn (Request $request, AuthContext $context) => $context->model::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
            ...$request->only('phone', 'birthdate', 'gender', 'country_id', 'state_id'),
        ]));

        // Set redirect path for authentication middleware
        Authenticate::redirectUsing(fn (Request $request) => $request->routeIs('website.*') ? route('website.login') : route('dashboard.login'));

        // Set redirect path for redirect if authenticated middleware
        RedirectIfAuthenticated::redirectUsing(fn (Request $request) => $request->routeIs('website.*') ? route('website.index') : route('dashboard.index'));
    }
}
