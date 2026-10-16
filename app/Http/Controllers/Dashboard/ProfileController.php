<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Redot\Http\Controllers\Controller;
use Redot\Traits\CanUploadFile;

class ProfileController extends Controller
{
    use CanUploadFile;

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('dashboard.profile.edit', [
            'admin' => current_admin(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'profile_picture' => ['nullable', 'image', 'max:1024'],
            'name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(Admin::class)->ignore($request->user()->id)],
            'password' => ['nullable', 'string', 'confirmed', 'min:8', Password::defaults()],
        ]);

        $admin = $request->user();
        $admin->fill($request->only('name', 'email'));

        if ($request->filled('password')) {
            $admin->password = $request->input('password');
        }

        if ($request->hasFile('profile_picture')) {
            $admin->profile_picture = $this->uploadFile($request->file('profile_picture'), 'dashboard/profile_pictures');
        }

        $admin->save();

        return $this->updated(__('Profile'));
    }
}
