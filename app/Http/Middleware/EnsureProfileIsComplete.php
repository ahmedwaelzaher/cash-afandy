<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('website.*') || $request->routeIs(
            'website.profile.complete*', 'website.logout', 'website.verification.*',
            'website.password.*', 'website.static-pages.show', 'website.health-check',
            'website.push-subscriptions.*', 'website.login*', 'website.register*',
            'website.socialite.*', 'website.magic-link*',
        )) return $next($request);

        $user = $request->user('users');
        if (! $user || $user->hasCompletedProfile()) return $next($request);

        $url = route('website.profile.complete', ['locale' => $request->route('locale') ?? app()->getLocale()]);
        if ($request->expectsJson()) {
            return response()->json(['message' => __('Please complete your account details to continue.'), 'redirect' => $url], 409);
        }

        return $request->isMethod('GET') ? redirect()->guest($url) : redirect()->to($url);
    }
}
