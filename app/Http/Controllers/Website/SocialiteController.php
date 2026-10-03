<?php

namespace App\Http\Controllers\Website;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Redot\Http\Controllers\Controller;
use Throwable;

class SocialiteController extends Controller
{
    /**
     * The supported OAuth providers.
     */
    public const PROVIDERS = ['google', 'facebook'];

    /**
     * Redirect the user to the provider's authentication page.
     */
    public function redirect(string $provider)
    {
        abort_unless(static::enabled($provider), 404);

        return $this->driver($provider)->redirect();
    }

    /**
     * Handle the provider callback and log the user in.
     */
    public function callback(Request $request, string $provider)
    {
        abort_unless(static::enabled($provider), 404);

        try {
            $socialUser = $this->driver($provider)->user();
        } catch (Throwable) {
            return $this->error(__('Unable to sign in with :provider, please try again.', ['provider' => Str::headline($provider)]), 'website.login');
        }

        if (! $socialUser->getEmail()) {
            return $this->error(__('Your :provider account has no email address.', ['provider' => Str::headline($provider)]), 'website.login');
        }

        $user = User::withTrashed()
            ->where("{$provider}_id", $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        if ($user?->trashed()) {
            return $this->error(__('Your account has been deactivated.'), 'website.login');
        }

        $user ??= $this->createUser($socialUser);

        if ($user->{"{$provider}_id"} !== $socialUser->getId()) {
            $user->forceFill(["{$provider}_id" => $socialUser->getId()]);
        }

        // The provider has already verified the email address.
        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()]);
        }

        $user->last_login_at = now();
        $user->save();

        Auth::guard('users')->login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('website.index'));
    }

    /**
     * Determine whether the given provider is configured.
     */
    public static function enabled(string $provider): bool
    {
        return in_array($provider, static::PROVIDERS, true)
            && filled(setting($provider . '_client_id'))
            && filled(setting($provider . '_client_secret'));
    }

    /**
     * Create a new user from the provider's user.
     */
    protected function createUser(SocialiteUser $socialUser): User
    {
        [$firstName, $lastName] = array_pad(explode(' ', trim($socialUser->getName() ?? ''), 2), 2, '');

        $user = new User([
            'first_name' => $firstName ?: Str::before($socialUser->getEmail(), '@'),
            'last_name' => $lastName,
            'email' => $socialUser->getEmail(),
            // Social users can set a real password later through "Forgot Password".
            'password' => Str::password(32),
        ]);

        // Verified before the Registered event, so no verification email is sent.
        $user->forceFill(['email_verified_at' => now()])->save();

        event(new Registered($user));

        return $user;
    }

    /**
     * Get the Socialite driver configured from the dashboard settings.
     */
    protected function driver(string $provider)
    {
        config(["services.$provider" => [
            'client_id' => setting($provider . '_client_id'),
            'client_secret' => setting($provider . '_client_secret'),
            'redirect' => route('website.socialite.callback', ['provider' => $provider]),
        ]]);

        return Socialite::driver($provider);
    }
}
