<?php

namespace App\Http\Controllers\Website;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Redot\Http\Controllers\Controller;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('website.profile.edit', [
            'user' => current_user(),
            'details' => $this->personalDetails(current_user()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'first_name' => ['string', 'max:255'],
            'last_name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(User::class)->ignore($request->user()->id)],
            'password' => ['nullable', 'string', 'confirmed', 'min:8', Password::defaults()],
            ...User::personalDetailsRules(),
        ]);

        $user = $request->user();
        $user->fill($request->only('first_name', 'last_name', 'email', 'phone', 'birthdate', 'gender'));

        if ($emailWasChanged = $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->filled('password')) {
            $user->password = $request->input('password');
        }

        $user->save();

        if ($emailWasChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $this->updated(__('Profile'));
    }

    /**
     * Update the authenticated user's preferences.
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['sometimes', 'required', 'string', 'in:light,dark'],
            'language' => ['sometimes', 'required', 'string', 'in:' . implode(',', setting('website_locales'))],
        ]);

        $request->user()->preferences()->update($validated);

        return $this->updated(__('Preferences'));
    }

    /**
     * Get the user's personal details as form values.
     */
    protected function personalDetails(User $user): array
    {
        return [
            'phone' => $user->phone,
            'birthdate' => $user->birthdate?->format('Y-m-d'),
            'gender' => $user->gender?->value,
        ];
    }
}
