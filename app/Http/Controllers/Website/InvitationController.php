<?php

namespace App\Http\Controllers\Website;

use App\Actions\Auth\ReferralAttribution;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class InvitationController extends Controller
{
    public function __invoke(Request $request, ReferralAttribution $referrals)
    {
        if (auth('users')->check()) {
            $referrals->forget($request);

        } else {
            $input = $request->validate(['referral_code' => ['nullable', 'string', 'max:64']]);
            if (filled($input['referral_code'] ?? null)) $referrals->capture($request, $input['referral_code']);
        }

        return response()->json($referrals->browserState($request));
    }
}
