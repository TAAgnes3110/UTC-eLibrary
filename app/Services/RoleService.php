<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RoleService
{
    /** @return Collection<int, Role> */
    public function index(): Collection
    {
        return Role::all();
    }

    public function store(array $validated): Role
    {
        return Role::create([
            'name' => $validated['name'],
            'guard_name' => 'api',
        ]);
    }

    public function show(int|string $id): Role
    {
        $role = Role::findOrFail($id);
        $role->load('permissions');
        return $role;
    }

    public function update(Role $role, array $validated): Role
    {
        $role->update(['name' => $validated['name']]);
        return $role;
    }

    public function destroy(int|string $id): void
    {
        $role = Role::findOrFail($id);
        $role->delete();
    }

    public function addPermission(Role $role, string $permissionName): Role
    {
        $role->givePermissionTo($permissionName);
        return $role->load('permissions');
    }

    public function removePermission(Role $role, string $permissionName): Role
    {
        $role->revokePermissionTo($permissionName);
        return $role->load('permissions');
    }
}
