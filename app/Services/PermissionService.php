<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /** @return Collection<int, Permission> */
    public function index(): Collection
    {
        return Permission::all();
    }

    public function store(array $validated): Permission
    {
        return Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'api',
        ]);
    }
}
