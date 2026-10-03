<?php

namespace App\Http\Controllers\Website;

use App\Enums\Gender;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Carbon\Carbon;
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

        $details = $this->personalDetails($socialUser);

        // Only fill what the user hasn't set already, the rest is confirmed on the completion page.
        $user->birthdate ??= $this->fullBirthdate($details);
        $user->gender ??= Gender::tryFrom($details['gender'] ?? '');

        $user->last_login_at = now();
        $user->save();

        Auth::guard('users')->login($user, true);
        $request->session()->regenerate();

        if (! $user->hasCompletedProfile()) {
            $request->session()->put('profile_completion', $details);

            return redirect()->route('website.profile.complete');
        }

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

        $driver = Socialite::driver($provider);

        if ($provider === 'facebook') {
            $driver->scopes(['user_birthday', 'user_gender', 'user_location'])
                ->fields(['name', 'email', 'birthday', 'gender', 'location']);
        }

        return $driver;
    }

    /**
     * Extract the personal details the provider shared, used to prefill the completion page.
     */
    protected function personalDetails(SocialiteUser $socialUser): array
    {
        $raw = $socialUser->getRaw();

        // Facebook sends MM/DD/YYYY, or only MM/DD or YYYY depending on the user's privacy settings.
        $parts = array_map('intval', explode('/', $raw['birthday'] ?? ''));

        [$month, $day, $year] = match (count($parts)) {
            3 => $parts,
            2 => [...$parts, null],
            default => [null, null, $parts[0] ?: null],
        };

        // Facebook location is a free-text place name, e.g. "Cairo, Egypt", so match each part by name.
        $segments = array_filter(array_map('trim', explode(',', $raw['location']['name'] ?? '')));

        $country = Country::query()->whereIn('name', $segments)->orWhereIn('native', $segments)->first() ?? website_country();

        $state = State::query()
            ->where('country_id', $country?->id)
            ->where(fn ($query) => $query->whereIn('name', $segments)->orWhereIn('native', $segments))
            ->first();

        return [
            'birth_day' => $day,
            'birth_month' => $month,
            'birth_year' => $year,
            'gender' => $raw['gender'] ?? null,
            'country_id' => $country?->id,
            'state_id' => $state?->id,
        ];
    }

    /**
     * Get the birthdate from the personal details, when the provider shared all of its parts.
     */
    protected function fullBirthdate(array $details): ?Carbon
    {
        ['birth_day' => $day, 'birth_month' => $month, 'birth_year' => $year] = $details;

        return $day && $month && $year && checkdate($month, $day, $year) ? Carbon::create($year, $month, $day) : null;
    }
}
