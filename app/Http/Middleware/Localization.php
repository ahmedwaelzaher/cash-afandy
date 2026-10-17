<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Redot\Http\Middleware\Localization as CoreLocalization;
use Symfony\Component\HttpFoundation\Response;

class Localization extends CoreLocalization
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        if ($scope !== 'website') {
            return parent::handle($request, $next, $scope);
        }

        $locales = setting('website_locales');
        $supported = fn ($locale) => is_string($locale) && in_array($locale, $locales, true);
        $browserLanguages = array_map(fn ($language) => strtolower(explode('_', $language)[0]), $request->getLanguages());
        $selected = Arr::first([
            $request->query('locale'),
            $request->route()->parameter('locale'),
            session('website_locale'),
            $request->cookie('website_locale'),
            ...$browserLanguages,
        ], $supported);

        // Only inject a fallback when neither the visitor nor the browser selected a supported language.
        if ($selected === null) {
            $locale = Arr::first([
                ...(website_ip_country()?->languages ?? []),
                setting('default_locale'),
                ...$locales,
            ], $supported);

            session()->put('website_locale', $locale);
        }

        return parent::handle($request, $next, $scope);
    }
}
