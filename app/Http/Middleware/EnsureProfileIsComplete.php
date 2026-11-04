<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('users');
        if (! $user || $user->hasCompletedProfile()) return $next($request);

        $url = route('website.profile.complete', ['locale' => $request->route('locale') ?? app()->getLocale()]);
        if ($request->expectsJson()) {
            return response()->json(['message' => __('Please complete your account details to continue.'), 'redirect' => $url], 409);
        }

        return $request->isMethod('GET') ? redirect()->guest($url) : redirect()->to($url);
    }
}
