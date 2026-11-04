<?php

namespace App\Http\Controllers\Website;

use App\Actions\Wallets\InitializeWallet;
use App\Enums\Gender;
use App\Enums\NotificationType;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Redot\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function complete(Request $request)
    {
        $user = $request->user('users')->load('wallet');
        if ($user->hasCompletedProfile()) return redirect()->intended(route('website.index'));

        return view('website.profile.complete', ['user' => $user]);
    }

    public function storeCompletion(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'country_id' => ['required', 'integer', Rule::in(Country::forAccounts()->pluck('id')->all())],
            'account_country_confirmation' => ['accepted'],
            'terms_accepted' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $user = User::whereKey($request->user('users')->id)->lockForUpdate()->firstOrFail();
            $user->fill(Arr::only($validated, ['first_name', 'last_name', 'gender']))->save();
            app(InitializeWallet::class)->handle($user, (int) $validated['country_id']);
        }, 3);

        return redirect()->intended(route('website.index'))->with('success', __('Your profile has been completed.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        current_user()->loadCount('acceptedReferrals');

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
        // The shared toggle submits the native checkbox value when checked.
        if ($request->input('notifications_enabled') === 'on') {
            $request->merge(['notifications_enabled' => 1]);
        }

        $validated = $request->validate([
            'theme' => ['sometimes', 'required', 'string', 'in:light,dark'],
            'language' => ['sometimes', 'required', 'string', 'in:' . implode(',', setting('website_locales'))],
            'notifications_enabled' => ['sometimes', 'required', 'boolean'],
            'notification_types' => ['exclude_without:notifications_enabled', 'exclude_if:notifications_enabled,0', 'sometimes', 'array'],
            'notification_types.*' => ['required', 'distinct', Rule::enum(NotificationType::class)],
            'notification_type_options' => ['exclude_without:notifications_enabled', 'exclude_if:notifications_enabled,0', 'sometimes', 'array', 'min:1'],
            'notification_type_options.*' => ['required', 'distinct', Rule::enum(NotificationType::class)],
        ]);

        if ($request->boolean('notifications_enabled')) {
            // Limit changes to the choices actually rendered, including forms opened before an update.
            $options = $validated['notification_type_options'] ?? [NotificationType::General->value];
            $excluded = $request->user()->preferences?->excludedNotificationTypes() ?? [];
            $validated['notification_types'] = ['disabled' => array_values(array_unique(array_merge(
                array_diff($excluded, $options),
                array_diff($options, $validated['notification_types'] ?? []),
            )))];
        }
        unset($validated['notification_type_options']);

        $request->user()->preferences()->update($validated);

        if ($request->has('notifications_enabled')) {
            return $this->updated(__('Preferences'), 'website.profile.edit', ['section' => 'notifications']);
        }

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
