<?php

namespace Tests\Feature\Roles;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\Backend\ActsAsApiUser;
use Tests\TestCase;

class RoleApiSecurityTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    #[Test]
    public function student_cannot_list_roles(): void
    {
        [, $token] = $this->createUserAndToken();

        $this->getJson('/api/v1/roles', $this->apiTokenHeaders($token))->assertStatus(403);
    }

    #[Test]
    public function student_cannot_create_role(): void
    {
        [, $token] = $this->createUserAndToken();

        $this->postJson('/api/v1/roles', ['name' => 'hacker_role'], $this->apiTokenHeaders($token))
            ->assertStatus(403);
    }

    #[Test]
    public function student_cannot_add_permission_to_role(): void
    {
        [, $token] = $this->createUserAndToken();
        $role = Role::firstOrCreate(['name' => 'api_test_role', 'guard_name' => 'api']);

        $this->postJson("/api/v1/roles/{$role->id}/permissions", [
            'permission' => 'books.view',
        ], $this->apiTokenHeaders($token))->assertStatus(403);
    }

    #[Test]
    public function librarian_can_list_roles(): void
    {
        [, $token] = $this->createLibrarianUserAndToken();

        $this->getJson('/api/v1/roles', $this->apiTokenHeaders($token))->assertStatus(200);
    }

    #[Test]
    public function create_role_with_empty_name_returns_422(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $this->postJson('/api/v1/roles', ['name' => ''], $this->apiTokenHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_role_with_sql_injection_name_is_rejected_or_sanitized(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->postJson('/api/v1/roles', [
            'name' => "'; DROP TABLE roles; --",
        ], $this->apiTokenHeaders($token));

        $this->assertContains($response->status(), [201, 422]);
        $this->assertDatabaseMissing('roles', ['name' => "'; DROP TABLE roles; --"]);
    }
}
