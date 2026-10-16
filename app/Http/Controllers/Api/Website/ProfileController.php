<?php

namespace App\Http\Controllers\Api\Website;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Redot\Http\Controllers\Controller;

class ProfileController extends Controller
{
    /**
     * Display the current user.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->respond($request->user());
    }

    /**
     * Update the current user.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => ['string', 'max:255'],
            'last_name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(User::class)->ignore($request->user()->id)],
            'password' => ['nullable', 'string', 'confirmed', 'min:8', Password::defaults()],
        ]);

        $user = $request->user();
        $user->fill($request->only('first_name', 'last_name', 'email'));

        if ($emailWasChanged = $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        $user->save();

        if ($emailWasChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $this->respond($user);
    }
}
