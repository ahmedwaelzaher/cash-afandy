<?php

namespace App\Actions\Auth;

use App\Actions\Wallets\InitializeWallet;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            'country_id' => ['required', 'integer', Rule::in(Country::forAccounts()->pluck('id')->all())],
            'account_country_confirmation' => ['accepted'],
            'terms_accepted' => ['accepted'],
            ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
        ];
    }

    /**
     * Create the user from the validated input.
     */
    public function create(array $input, Panel $panel): User
    {
        $user = DB::transaction(function () use ($input) {
            $user = User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'password' => $input['password'],
                ...Arr::only($input, ['phone', 'birthdate', 'gender']),
            ]);
            app(InitializeWallet::class)->handle($user, (int) $input['country_id']);

            return $user;
        });

        return $user;
    }
}
