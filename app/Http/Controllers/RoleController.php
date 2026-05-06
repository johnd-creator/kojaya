<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRoleRequest;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $this->authorizePermission('manage_roles');

        $roles = Role::withCount('users')->get();

        return Inertia::render('Role/Index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): Response
    {
        $this->authorizePermission('manage_roles');

        // Pass all available permissions grouped by something, or just flat for now
        $permissions = Permission::all();

        return Inertia::render('Role/Edit', [
            'role' => $role->load('permissions'),
            'permissions' => $permissions,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->authorizePermission('manage_roles');

        $validated = $request->validated();

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role permissions updated successfully.');
    }
}
