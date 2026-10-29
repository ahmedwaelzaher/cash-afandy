<?php

namespace App\Providers;

use App\Actions\Auth\CreateUser;
use App\Actions\Auth\PendingAction;
use App\Http\Responses\LoginResponse;
use App\Models\Admin;
use App\Models\PushSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login as AuthLogin;
use Illuminate\Auth\Events\Logout as AuthLogout;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Redot\Auth\Contracts\CreatesUsers;
use Redot\Auth\Facades\RedotAuth;
use Redot\Auth\Features\EmailVerification;
use Redot\Auth\Features\LockScreen;
use Redot\Auth\Features\Login;
use Redot\Auth\Features\Logout;
use Redot\Auth\Features\MagicLink;
use Redot\Auth\Features\PasswordReset;
use Redot\Auth\Features\Registration;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any authentication services.
     */
    public function boot(): void
    {
        $this->configurePanels();
        $this->configureNotifications();
        $this->configureRedirects();

        Event::listen(AuthLogin::class, function (AuthLogin $event) {
            if ($event->guard === 'users' && $event->user instanceof User && request()->hasSession()) {
                $hash = request()->session()->get('push_subscription_token_hash');
                if ($hash) {
                    PushSubscription::query()->whereMorphedTo('notifiable', null)
                        ->where('token_hash', $hash)->whereNull('disabled_at')
                        ->update(['notifiable_type' => $event->user->getMorphClass(), 'notifiable_id' => $event->user->getKey()]);
                }
                app(PendingAction::class)->complete(request(), $event->user);
            }
        });

        Event::listen(AuthLogout::class, function (AuthLogout $event) {
            if ($event->guard === 'users' && $event->user && request()->hasSession()) {
                $hash = request()->session()->get('push_subscription_token_hash');
                if ($hash) {
                    PushSubscription::query()->whereMorphedTo('notifiable', $event->user)
                        ->where('token_hash', $hash)
                        ->update(['notifiable_type' => null, 'notifiable_id' => null]);
                }
            }
        });
    }

    /**
     * Define the auth panels registered by the route files.
     */
    protected function configurePanels(): void
    {
        RedotAuth::panel('dashboard')
            ->guard('admins')
            ->scope(fn ($query) => $query->where('active', true))
            ->views('dashboard.auth')
            ->features(
                Login::make(),
                PasswordReset::make(),
                MagicLink::make(),
                Logout::make(),
                LockScreen::make()->protect('dashboard'),
            );

        RedotAuth::panel('dashboard-api')
            ->api()
            ->guard('admins-api')
            ->scope(fn ($query) => $query->where('active', true))
            ->features(
                Login::make(),
                PasswordReset::make(),
                Logout::make(),
            );

        RedotAuth::panel('website')
            ->guard('users')
            ->views('website.auth')
            ->using(CreatesUsers::class, CreateUser::class)
            ->using(\Redot\Auth\Responses\LoginResponse::class, LoginResponse::class)
            ->features(
                Login::make(),
                Registration::make(),
                PasswordReset::make(),
                MagicLink::make(),
                EmailVerification::make(),
                Logout::make(),
            );

        RedotAuth::panel('website-api')
            ->api()
            ->guard('users-api')
            ->using(CreatesUsers::class, CreateUser::class)
            ->features(
                Login::make(),
                Registration::make(),
                PasswordReset::make(),
                EmailVerification::make(),
                Logout::make(),
            );
    }

    /**
     * Point password reset and email verification links at the right panel.
     */
    protected function configureNotifications(): void
    {
        ResetPassword::createUrlUsing(function (Admin|User $user, string $token) {
            $route = $user instanceof Admin ? 'dashboard.password.reset' : 'website.password.reset';

            return route($route, [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], true);
        });

        VerifyEmail::createUrlUsing(function (Admin|User $user) {
            $route = $user instanceof Admin ? 'dashboard.verification.verify' : 'website.verification.verify';
            $expiresAt = Carbon::now()->addMinutes(config('auth.verification.expire', 60));

            return URL::temporarySignedRoute($route, $expiresAt, [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ], true);
        });
    }

    /**
     * Send guests and authenticated users to the right panel.
     */
    protected function configureRedirects(): void
    {
        Authenticate::redirectUsing(fn (Request $request) => $request->routeIs('website.*') ? route('website.login') : route('dashboard.login'));

        RedirectIfAuthenticated::redirectUsing(fn (Request $request) => $request->routeIs('website.*') ? route('website.index') : route('dashboard.index'));
    }
}
