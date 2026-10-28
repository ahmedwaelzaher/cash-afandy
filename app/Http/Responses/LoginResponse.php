<?php

namespace App\Http\Responses;

use Redot\Auth\Panel;
use Redot\Auth\Responses\LoginResponse as CoreLoginResponse;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse extends CoreLoginResponse
{
    protected function web(Panel $panel): Response
    {
        if (request()->expectsJson() && request()->header('X-Login-Modal') === 'true') {
            return response()->json([
                'csrfToken' => csrf_token(),
                'redirect' => request()->session()->pull('url.intended'),
            ])->header('Cache-Control', 'no-store');
        }

        return parent::web($panel);
    }
}
