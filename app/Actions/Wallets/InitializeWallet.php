<?php

namespace App\Actions\Wallets;

use App\Models\Country;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitializeWallet
{
    public function handle(User $user, int $countryId): Wallet
    {
        $wallet = DB::transaction(function () use ($user, $countryId) {
            $owner = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $country = Country::forAccounts()->find($countryId);
            if (! $country || ! preg_match('/^[A-Z]{3}$/D', $country->currency)) {
                throw ValidationException::withMessages(['country_id' => __('Please select a supported account country.')]);
            }
            $wallet = $owner->wallet()->lockForUpdate()->first();
            if ($wallet && $wallet->currency !== $country->currency) {
                throw ValidationException::withMessages(['country_id' => __('The selected country must match your existing wallet currency.')]);
            }
            $owner->forceFill(['country_id' => $country->id])->save();

            return $wallet ?? $owner->wallet()->create(['currency' => $country->currency]);
        }, 3);
        $user->refresh()->setRelation('wallet', $wallet);

        return $wallet;
    }
}
