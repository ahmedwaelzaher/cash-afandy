<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Redirect users with missing personal details to the profile completion page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('users');

        if ($user && ! $user->hasCompletedProfile()) {
            // Remember GET pages only, so the user lands back there after completing the profile.
            return $request->isMethod('GET')
                ? redirect()->guest(route('website.profile.complete'))
                : redirect()->route('website.profile.complete');
        }

        return $next($request);
    }
}
