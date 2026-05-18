<?php

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Vai trò & quyền (10 case).
 */
class RoleModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    #[Test]
    public function case01_student_cannot_list_roles(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/roles', $h)->assertStatus(403);
    }

    #[Test]
    public function case02_admin_lists_roles(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/roles', $h)->assertStatus(200);
    }

    #[Test]
    public function case03_librarian_lists_roles(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/roles', $h)->assertStatus(200);
    }

    #[Test]
    public function case04_create_empty_name_returns_422(): void
    {
        [, $h] = $this->adminContext();
        $this->postJson('/api/v1/roles', ['name' => ''], $h)->assertStatus(422);
    }

    #[Test]
    public function case05_create_role_returns_201(): void
    {
        [, $h] = $this->adminContext();
        $this->postJson('/api/v1/roles', ['name' => 'role_test_'.uniqid()], $h)
            ->assertStatus(201);
    }

    #[Test]
    public function case06_student_cannot_create_role(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/roles', ['name' => 'evil'], $h)->assertStatus(403);
    }

    #[Test]
    public function case07_show_nonexistent_returns_404(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/roles/9999999', $h)->assertStatus(404);
    }

    #[Test]
    public function case08_permissions_index_returns_200(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/permissions', $h)->assertStatus(200);
    }

    #[Test]
    public function case09_add_permission_requires_permission_field(): void
    {
        [, $h] = $this->adminContext();
        $role = Role::firstOrCreate(['name' => 'perm_test_role', 'guard_name' => 'api']);
        $this->postJson("/api/v1/roles/{$role->id}/permissions", [], $h)->assertStatus(422);
    }

    #[Test]
    public function case10_student_cannot_add_permission(): void
    {
        [, $h] = $this->studentContext();
        $role = Role::firstOrCreate(['name' => 'perm_test_role2', 'guard_name' => 'api']);
        $this->postJson("/api/v1/roles/{$role->id}/permissions", [
            'permission' => 'books.view',
        ], $h)->assertStatus(403);
    }
}
