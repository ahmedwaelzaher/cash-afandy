<?php

namespace App\Http\Controllers\Website;

use App\Models\Country;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
            'currencies' => Country::query()->distinct()->orderBy('currency')->pluck('currency', 'currency'),
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
            'password' => ['nullable', 'confirmed', 'min:8', Password::defaults()],
            ...User::personalDetailsRules($request->country_id),
        ]);

        $user = $request->user();
        $user->fill($request->only('first_name', 'last_name', 'email', 'phone', 'birthdate', 'gender', 'country_id', 'state_id'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->filled('password')) {
            $user->password = $request->get('password');
        }

        $user->save();

        return $this->updated(__('Profile'));
    }

    /**
     * Show the form for completing the user's personal details.
     */
    public function complete(Request $request)
    {
        // Saved details win over the ones guessed from the social provider.
        $details = array_merge(
            $request->session()->get('profile_completion', []),
            array_filter($this->personalDetails($request->user())),
        );

        return view('website.profile.complete', [
            'details' => $details,
        ]);
    }

    /**
     * Store the user's completed personal details.
     */
    public function storeCompletion(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_day' => ['required', 'integer', 'between:1,31'],
            'birth_month' => ['required', 'integer', 'between:1,12'],
            'birth_year' => ['required', 'integer', 'min:1900'],
        ]);

        // The birthdate is picked as day / month / year, so check that they make a real past date.
        if (! checkdate($request->birth_month, $request->birth_day, $request->birth_year)
            || Carbon::create($request->birth_year, $request->birth_month, $request->birth_day)->isFuture()) {
            throw ValidationException::withMessages(['birth_day' => __('Please choose a valid birthdate.')]);
        }

        $request->merge([
            'birthdate' => sprintf('%04d-%02d-%02d', $request->birth_year, $request->birth_month, $request->birth_day),
        ]);

        $validated = $request->validate(User::personalDetailsRules($request->country_id));

        $request->user()->update([...$request->only('first_name', 'last_name'), ...$validated]);
        $request->session()->forget('profile_completion');

        return redirect()->intended(route('website.index'))->with('success', __('Your profile has been completed.'));
    }

    /**
     * Update the authenticated user's preferences.
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['sometimes', 'required', 'string', 'in:light,dark'],
            'language' => ['sometimes', 'required', 'string', 'in:' . implode(',', setting('website_locales'))],
            'country_id' => ['sometimes', 'nullable', 'exists:countries,id'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3', 'exists:countries,currency'],
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
            'birth_day' => $user->birthdate?->day,
            'birth_month' => $user->birthdate?->month,
            'birth_year' => $user->birthdate?->year,
            'gender' => $user->gender?->value,
            'country_id' => $user->country_id,
            'state_id' => $user->state_id,
        ];
    }
}
