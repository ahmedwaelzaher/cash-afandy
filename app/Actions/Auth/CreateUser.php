<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Redot\Auth\Contracts\CreatesUsers;
use Redot\Auth\Panel;

class CreateUser implements CreatesUsers
{
    /**
     * Get the validation rules for the registration request.
     */
    public function rules(Panel $panel): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . $panel->model()],
            'password' => ['required', 'confirmed', Password::defaults()],
            ...User::personalDetailsRules(requireGender: true),
            'country_id' => User::accountCountryRules(),
            'referral_code' => ['nullable', 'string', 'max:64'],
            'referral_context' => ['nullable', 'string', 'max:4096'],
            ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
        ];
    }

    /**
     * Create the user from the validated input.
     */
    public function create(array $input, Panel $panel): User
    {
        $request = request();
        $referrals = app(ReferralAttribution::class);
        $context = $referrals->resolve($request, $input, $panel->isApi());
        $user = DB::transaction(function () use ($input, $referrals, $context) {
            $user = User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'password' => $input['password'],
                ...Arr::only($input, ['phone', 'birthdate', 'gender']),
            ]);
            $user->initializeWallet((int) $input['country_id']);
            $referrals->attach($user, $context);

            return $user;
        });
        if (! $panel->isApi()) $referrals->forget($request);

        return $user;
    }
}
