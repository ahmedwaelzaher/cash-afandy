<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Redot\Http\Controllers\Controller;
use Redot\Traits\CanUploadFile;

class ProfileController extends Controller
{
    use CanUploadFile;

    /**
     * Display the current admin.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->respond($request->user());
    }

    /**
     * Update the current admin.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'profile_picture' => ['nullable', 'image', 'max:1024'],
            'name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(Admin::class)->ignore($request->user()->id)],
            'password' => ['nullable', 'string', 'confirmed', 'min:8', Password::defaults()],
        ]);

        $admin = $request->user();
        $admin->fill($request->only('name', 'email'));

        if ($request->hasFile('profile_picture')) {
            $admin->profile_picture = $this->uploadFile($request->file('profile_picture'), 'dashboard/profile_pictures');
        }

        if ($request->filled('password')) {
            $admin->password = $request->password;
        }

        $admin->save();

        return $this->respond($admin);
    }
}
