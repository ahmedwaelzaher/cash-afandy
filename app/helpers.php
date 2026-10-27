<?php

use App\Models\Admin;
use App\Models\Country;
use App\Models\User;
use Faker\Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Stevebauman\Location\Facades\Location;

/**
 * Get local font families from non-empty family directories.
 */
function theme_fonts(): array
{
    $path = public_path('assets/fonts');

    if (! File::isDirectory($path)) {
        return [];
    }

    return collect(File::directories($path))
        ->filter(fn ($directory) => count(File::files($directory)) > 0)
        ->mapWithKeys(fn ($directory) => [basename($directory) => Str::headline(basename($directory))])
        ->sortKeys()->all();
}

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
 * Get the IP-detected country, independently of the selected shopping market.
 */
function website_ip_country(): ?Country
{
    $code = Cache::remember('website-country-ip:' . request()->ip(), now()->addDay(), function () {
        $position = Location::get();

        return $position ? strtolower((string) $position->countryCode) : '';
    });

    return $code ? Country::code($code) : null;
}

/**
 * Get the website's currently selected country.
 *
 * Resolution order: an explicit `?country=` query param, the previously
 * selected country in the session or cookie, then the IP-detected country,
 * then the default market. Only supported shopping markets can be selected.
 *
 * Uses the default market when the countries dropdown is disabled.
 */
function website_country(): ?Country
{
    $default = setting('default_website_country');
    if (! setting('show_website_countries_dropdown')) {
        return Country::code($default);
    }

    $codes = setting('website_countries');

    $code = request()->query('country');

    if (! is_string($code) || ! in_array($code, $codes, true)) {
        $code = session('website_country');
    }

    if (! is_string($code) || ! in_array($code, $codes, true)) {
        $code = request()->cookie('website_country');
    }

    if (! is_string($code) || ! in_array($code, $codes, true)) {
        $code = website_ip_country()?->code;
    }

    $country = Country::code(in_array($code, $codes, true) ? $code : $default) ?? Country::code($default);

    if ($country) {
        session()->put('website_country', $country->code);
        cookie()->queue(cookie()->forever('website_country', $country->code));
    }

    return $country;
}

/** Encode an offer for browser reveal; the shared key is not a secrecy boundary. */
function encode_offer_code(string $code): string
{
    $iv = random_bytes(12);
    $encrypted = openssl_encrypt($code, 'aes-256-gcm', hash('sha256', 'cashafandy', true), OPENSSL_RAW_DATA, $iv, $tag);

    if ($encrypted === false) {
        throw new RuntimeException('Unable to encode the offer code.');
    }

    return base64_encode($iv . $encrypted . $tag);
}
