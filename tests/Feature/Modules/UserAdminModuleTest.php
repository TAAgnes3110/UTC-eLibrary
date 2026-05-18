<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Quản lý user admin (10 case).
 */
class UserAdminModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    #[Test]
    public function case01_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    #[Test]
    public function case02_student_forbidden(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/users', $h)->assertStatus(403);
    }

    #[Test]
    public function case03_admin_lists_users(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/users', $h)->assertStatus(200);
    }

    #[Test]
    public function case04_list_does_not_expose_password_hash(): void
    {
        User::factory()->create();
        [, $h] = $this->adminContext();
        $this->assertStringNotContainsString('$2y$', $this->getJson('/api/v1/users', $h)->content());
    }

    #[Test]
    public function case05_admin_updates_user_name(): void
    {
        $target = User::factory()->create();
        [, $h] = $this->adminContext();
        $this->putJson("/api/v1/users/{$target->id}", ['name' => 'Admin đổi tên'], $h)
            ->assertStatus(200);
        $this->assertSame('Admin đổi tên', $target->fresh()->name);
    }

    #[Test]
    public function case06_student_cannot_update_user(): void
    {
        $target = User::factory()->create();
        [, $h] = $this->studentContext();
        $this->putJson("/api/v1/users/{$target->id}", ['name' => 'Hack'], $h)->assertStatus(403);
    }

    #[Test]
    public function case07_show_nonexistent_returns_404(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/users/9999999', $h)->assertStatus(404);
    }

    #[Test]
    public function case08_trash_list_returns_200(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/users/trash', $h)->assertStatus(200);
    }

    #[Test]
    public function case09_export_returns_200(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/users/export', $h)->assertSuccessful();
    }

    #[Test]
    public function case10_student_cannot_delete_user(): void
    {
        $target = User::factory()->create();
        [, $h] = $this->studentContext();
        $this->deleteJson("/api/v1/users/{$target->id}", [], $h)->assertStatus(403);
    }
}
