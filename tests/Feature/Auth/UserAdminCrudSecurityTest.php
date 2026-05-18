<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CRUD & bảo mật API quản lý User (Admin).
 * Kiểm tra IDOR, mass-assignment, missing auth, và data integrity.
 */
class UserAdminCrudSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $role = Role::firstOrCreate(
            ['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => 'api'],
        );
        $user = User::factory()->create(['user_type' => RoleType::SUPER_ADMIN]);
        $user->assignRole($role);
        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    private function makeStudent(): array
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_request_to_user_list_returns_401(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    #[Test]
    public function student_cannot_list_users(): void
    {
        [, $token] = $this->makeStudent();
        $this->getJson('/api/v1/users', ['Authorization' => "Bearer $token"])
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_list_users(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/users', ['Authorization' => "Bearer $token"])
            ->assertStatus(200);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_update_user_name(): void
    {
        [$admin, $token] = $this->makeAdmin();
        $target = User::factory()->create();

        $this->putJson("/api/v1/users/{$target->id}", ['name' => 'Tên Mới'], ['Authorization' => "Bearer $token"])
            ->assertStatus(200);

        $this->assertEquals('Tên Mới', $target->fresh()->name);
    }

    #[Test]
    public function student_cannot_update_another_user(): void
    {
        [, $token] = $this->makeStudent();
        $other = User::factory()->create();

        $this->putJson("/api/v1/users/{$other->id}", ['name' => 'Hijacked'], ['Authorization' => "Bearer $token"])
            ->assertStatus(403);
    }

    #[Test]
    public function updating_user_with_nonexistent_faculty_id_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create();

        $this->putJson("/api/v1/users/{$target->id}", ['faculty_id' => 999999], ['Authorization' => "Bearer $token"])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['faculty_id']);
    }

    #[Test]
    public function updating_nonexistent_user_returns_404(): void
    {
        [, $token] = $this->makeAdmin();
        $this->putJson('/api/v1/users/9999999', ['name' => 'Ghost'], ['Authorization' => "Bearer $token"])
            ->assertStatus(404);
    }

    #[Test]
    public function updating_user_with_duplicate_email_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        User::factory()->create(['email' => 'taken@example.com']);
        $target = User::factory()->create(['email' => 'original@example.com']);

        $this->putJson("/api/v1/users/{$target->id}", ['email' => 'taken@example.com'], ['Authorization' => "Bearer $token"])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function update_with_own_email_does_not_trigger_unique_error(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create(['email' => 'same@example.com']);

        $this->putJson("/api/v1/users/{$target->id}", ['email' => 'same@example.com'], ['Authorization' => "Bearer $token"])
            ->assertStatus(200);
    }

    #[Test]
    public function user_code_cannot_be_changed_via_update_endpoint(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create(['code' => '111111111']);

        $this->putJson("/api/v1/users/{$target->id}", ['code' => '999999999'], ['Authorization' => "Bearer $token"])
            ->assertStatus(200);

        $this->assertEquals('111111111', $target->fresh()->code);
    }

    #[Test]
    public function update_with_empty_password_does_not_change_password(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create(['password' => 'original_pass']);
        $oldHash = $target->password;

        $this->putJson("/api/v1/users/{$target->id}", ['password' => ''], ['Authorization' => "Bearer $token"])
            ->assertStatus(200);

        $this->assertEquals($oldHash, $target->fresh()->password);
    }

    #[Test]
    public function update_with_very_long_name_is_rejected(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create();

        $this->putJson("/api/v1/users/{$target->id}", ['name' => str_repeat('A', 256)], ['Authorization' => "Bearer $token"])
            ->assertStatus(422);
    }

    #[Test]
    public function update_user_type_to_invalid_value_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create();

        $this->putJson("/api/v1/users/{$target->id}", ['user_type' => 'HACKER_ROLE'], ['Authorization' => "Bearer $token"])
            ->assertStatus(422);
    }

    // ── Delete & Restore ─────────────────────────────────────────────────────

    #[Test]
    public function admin_can_soft_delete_user(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create();

        $this->deleteJson("/api/v1/users/{$target->id}", [], ['Authorization' => "Bearer $token"])
            ->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    #[Test]
    public function deleting_nonexistent_user_returns_404(): void
    {
        [, $token] = $this->makeAdmin();

        $this->deleteJson('/api/v1/users/9999999', [], ['Authorization' => "Bearer $token"])
            ->assertStatus(404);
    }

    #[Test]
    public function student_cannot_delete_user(): void
    {
        [, $token] = $this->makeStudent();
        $other = User::factory()->create();

        $this->deleteJson("/api/v1/users/{$other->id}", [], ['Authorization' => "Bearer $token"])
            ->assertStatus(403);
    }

    #[Test]
    public function restore_deleted_user_succeeds(): void
    {
        [, $token] = $this->makeAdmin();
        $target = User::factory()->create();
        $target->delete();

        $this->postJson("/api/v1/users/restore/{$target->id}", [], ['Authorization' => "Bearer $token"])
            ->assertStatus(200);

        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    // ── IDOR (Insecure Direct Object Reference) ───────────────────────────────

    #[Test]
    public function show_user_with_string_id_is_handled_safely(): void
    {
        [, $token] = $this->makeAdmin();

        $this->getJson('/api/v1/users/not-an-integer', ['Authorization' => "Bearer $token"])
            ->assertStatus(404);
    }

    #[Test]
    public function show_user_with_float_id_returns_404(): void
    {
        [, $token] = $this->makeAdmin();

        $this->getJson('/api/v1/users/1.5', ['Authorization' => "Bearer $token"])
            ->assertStatus(404);
    }

    #[Test]
    public function show_user_with_negative_id_returns_404(): void
    {
        [, $token] = $this->makeAdmin();

        $this->getJson('/api/v1/users/-1', ['Authorization' => "Bearer $token"])
            ->assertStatus(404);
    }

    #[Test]
    public function force_delete_with_invalid_ids_array_is_rejected(): void
    {
        [, $token] = $this->makeAdmin();

        $this->deleteJson('/api/v1/users/force', ['ids' => 'not-array'], ['Authorization' => "Bearer $token"])
            ->assertStatus(422);
    }

    #[Test]
    public function force_delete_requires_ids_array(): void
    {
        [, $token] = $this->makeAdmin();

        $this->deleteJson('/api/v1/users/force', [], ['Authorization' => "Bearer $token"])
            ->assertStatus(422);
    }

    // ── Pagination & Filtering ────────────────────────────────────────────────

    #[Test]
    public function user_list_does_not_expose_password_hashes(): void
    {
        [, $token] = $this->makeAdmin();
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users', ['Authorization' => "Bearer $token"])
            ->assertStatus(200);

        $body = $response->content();
        $this->assertStringNotContainsString('$2y$', $body);
        $this->assertStringNotContainsString('"password"', $body);
    }

    #[Test]
    public function user_list_with_invalid_per_page_returns_422(): void
    {
        [, $token] = $this->makeAdmin();

        $this->getJson('/api/v1/users?per_page=99999', ['Authorization' => "Bearer $token"])
            ->assertStatus(422);
    }

    #[Test]
    public function toggle_status_for_nonexistent_user_returns_404(): void
    {
        [, $token] = $this->makeAdmin();

        $this->postJson('/api/v1/users/9999999/toggle-status', [], ['Authorization' => "Bearer $token"])
            ->assertStatus(404);
    }
}
