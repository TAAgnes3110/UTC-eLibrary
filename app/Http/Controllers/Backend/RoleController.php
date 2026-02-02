<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return $this->jsonResponse(Role::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'api']);
        return $this->jsonResponse($role, 201);
    }

    public function show($id)
    {
        $role = Role::findOrFail($id);
        $role->load('permissions');
        return $this->jsonResponse($role);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => $validated['name']]);
        return $this->jsonResponse($role);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return $this->jsonResponse(['message' => 'Role deleted successfully']);
    }

    public function addPermission(Request $request, $id)
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);
        $role->givePermissionTo($request->permission);

        return $this->jsonResponse(['message' => 'Permission added to role', 'role' => $role->load('permissions')]);
    }

    public function removePermission(Request $request, $id)
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);
        $role->revokePermissionTo($request->permission);

        return $this->jsonResponse(['message' => 'Permission removed from role', 'role' => $role->load('permissions')]);
    }
}
