<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Arr;
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
            ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
        ];
    }

    /**
     * Create the user from the validated input.
     */
    public function create(array $input, Panel $panel): User
    {
        return User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'password' => $input['password'],
            ...Arr::only($input, ['phone', 'birthdate', 'gender']),
        ]);
    }
}
