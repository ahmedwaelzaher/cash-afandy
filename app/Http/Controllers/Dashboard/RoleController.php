<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.roles.create', [
            'permissions' => $this->getPermissions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'exists:permissions,name'],
        ]);

        $role = Role::create($validated);

        $role->syncPermissions($validated['permissions'] ?? []);

        if ($request->has('from-select')) {
            return $this->dispatchBrowserEvent($request->query('from-select'), [
                'value' => $role->name,
            ]);
        }

        return $this->created(__('Role'), 'dashboard.roles.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        return view('dashboard.roles.edit', [
            'role' => $role,
            'permissions' => $this->getPermissions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'exists:permissions,name'],
        ]);

        if (
            Permission::findByName('dashboard.roles.edit')->roles()->count() === 1
            && $role->hasPermissionTo('dashboard.roles.edit')
            && ! in_array('dashboard.roles.edit', $validated['permissions'] ?? [])
        ) {
            // Prevent `dashboard.roles.edit` to be unaccessable
            return $this->error(__('Edit role permission needs to be assigned to at least one role.'));
        }

        $role->update($validated);

        $role->syncPermissions($validated['permissions'] ?? []);

        return $this->updated(__('Role'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return $this->error(__('Role is used by a user.'));
        }

        $role->delete();

        return $this->deleted(__('Role'));
    }

    /**
     * Get all permissions grouped by their group name.
     */
    protected function getPermissions()
    {
        return Permission::all()->groupBy(function ($item, $key) {
            $parts = explode('.', str_replace('dashboard.', '', $item->name));
            array_pop($parts);

            return implode('.', $parts);
        });
    }
}
