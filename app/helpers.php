<?php

use App\Models\Admin;
use App\Models\Country;
use App\Models\User;
use Faker\Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

/**
 * Get a static page url by the given slug.
 */
function static_page_url(string $slug, array $parameters = [], bool $absolute = true): string
{
    return route('website.static-pages.show', array_merge($parameters, ['staticPage' => $slug]), $absolute);
}

/**
 * Get a Faker instance for the given application locale.
 */
function fake_locale(string $locale): Generator
{
    return fake(config("app.faker_locales.{$locale}", config('app.faker_locale')));
}

/**
 * Get the current logged in admin.
 */
function current_admin(): ?Admin
{
    return auth('admins')->user() ?? auth('admins-api')->user();
}

/**
 * Get the current logged in user.
 */
function current_user(): ?User
{
    return auth('users')->user() ?? auth('users-api')->user();
}

/**
 * Get the active browser sessions for the current account on the given guard.
 */
function current_sessions(string $guard): Collection
{
    if (config('session.driver') !== 'database' || ! $user = auth($guard)->user()) {
        return collect();
    }

    return $user->sessions()->wherePivot('guard', $guard)
        ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->timestamp)
        ->orderByDesc('last_activity')->get();
}

/**
 * Determine whether the current request belongs to the dashboard scope.
 */
function is_dashboard_request(): bool
{
    if (request()->routeIs('dashboard.*')) {
        return true;
    }

    // Unmatched requests (e.g. 404s) carry no dashboard route, so compare the
    // URL against the dashboard root, which already reflects the configured
    // prefix and the current locale.
    $root = route('dashboard.index');

    return url()->current() === $root || str_starts_with(url()->current(), "{$root}/");
}

/**
 * Get the website's currently selected country.
 *
 * Resolution order: an explicit `?country=` query param, the previously
 * selected country in the session, then the visitor's IP-detected country -
 * restricted to the countries the website currently supports.
 *
 * Returns null when the countries dropdown is disabled in settings, meaning
 * content should not be filtered by country (all countries by default).
 */
function website_country(): ?Country
{
    $default = setting('default_website_country');
    if (! setting('show_website_countries_dropdown')) {
        return Country::code($default);
    }

    $codes = setting('website_countries');

    $code = request()->query('country');

    if ($code && in_array($code, $codes, true)) {
        session()->put('website_country', $code);
    } else {
        $code = session('website_country');
    }

    if (! in_array($code, $codes, true)) {
        $code = Cache::remember(
            'website-country-ip:' . request()->ip(),
            now()->addDay(),
            function () {
                $position = Location::get();

                return $position ? strtolower((string) $position->countryCode) : '';
            }
        );

        if (! in_array($code, $codes, true)) {
            $code = $default;
        }

        session()->put('website_country', $code);
    }

    return Country::code($code) ?? Country::code($default);
}
