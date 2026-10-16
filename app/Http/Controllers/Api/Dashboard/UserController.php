<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return $this->respond(User::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
        ]);

        $user = User::invite($validated);

        return $this->respond($user, 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        return $this->respond($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return $this->respond($user, 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->respond(message: 'User deleted successfully');
    }

    /**
     * Restore the specified resource.
     */
    public function restore(User $user): JsonResponse
    {
        $user->restore();

        return $this->respond($user, 'User restored successfully');
    }
}
