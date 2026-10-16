<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return $this->respond(Admin::with('roles:id,name')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'role' => 'nullable|exists:roles,name',
        ]);

        Admin::invite([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ], $validated['role'] ?? null);

        return $this->respond(message: 'Admin created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin): JsonResponse
    {
        return $this->respond($admin->load('roles:id,name'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'sometimes|nullable|exists:roles,name',
            'active' => 'sometimes|boolean',
        ]);

        if ($request->has('role')) {
            $admin->syncRoles($validated['role'] ?? []);
        }

        if ($request->has('active')) {
            $admin->update(['active' => $validated['active']]);
        }

        return $this->respond($admin->load('roles:id,name'), 'Admin updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Admin $admin): JsonResponse
    {
        if ($admin->is($request->user())) {
            $this->fail('You cannot delete yourself.', 403);
        }

        $admin->delete();

        return $this->respond(message: 'Admin deleted successfully');
    }
}
