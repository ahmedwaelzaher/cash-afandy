<?php

namespace App\Http\Responses;

use App\Models\User;
use Redot\Auth\Panel;
use Redot\Auth\Responses\LoginResponse as CoreLoginResponse;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse extends CoreLoginResponse
{
    protected function web(Panel $panel): Response
    {
        $completion = $this->user instanceof User && ! $this->user->hasCompletedProfile()
            ? route('website.profile.complete') : null;
        if (request()->expectsJson() && request()->header('X-Login-Modal') === 'true') {
            return response()->json([
                'csrfToken' => csrf_token(),
                'redirect' => $completion ?? request()->session()->pull('url.intended'),
            ])->header('Cache-Control', 'no-store');
        }

        if ($completion) return redirect()->to($completion);

        return parent::web($panel);
    }
}
