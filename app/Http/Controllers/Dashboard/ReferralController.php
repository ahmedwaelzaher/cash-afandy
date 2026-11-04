<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class ReferralController extends Controller
{
    public function index()
    {
        return view('dashboard.referrals.index', [
            'total' => Referral::accepted()->count(),
            'verified' => Referral::accepted()->whereHas('referredUser', fn ($query) => $query->whereNotNull('email_verified_at'))->count(),
            'topReferrers' => User::withTrashed()->has('acceptedReferrals')->withCount('acceptedReferrals')->orderByDesc('accepted_referrals_count')->limit(5)->get(),
        ]);
    }

    public function update(Request $request, Referral $referral)
    {
        $validated = $request->validate(['accepted' => ['required', 'boolean']]);
        $referral->update($validated);

        return $this->updated(__('Invitations'));
    }
}
