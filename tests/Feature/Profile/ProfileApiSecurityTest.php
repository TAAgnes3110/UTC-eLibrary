<?php

namespace Tests\Feature\Profile;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test Profile API – bảo mật, data isolation, mass-assignment.
 */
class ProfileApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $extra = []): array
    {
        $user = User::factory()->create(array_merge([
            'user_type' => RoleType::STUDENT,
            'email' => 'profile@example.com',
            'password' => 'OldPassword1!',
        ], $extra));

        return [$user, JWTAuth::fromUser($user)];
    }

    private function auth(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_cannot_view_profile(): void
    {
        $this->getJson('/api/v1/me/profile')->assertStatus(401);
    }

    #[Test]
    public function unauthenticated_cannot_update_profile(): void
    {
        $this->putJson('/api/v1/me/profile', ['name' => 'Test'])->assertStatus(401);
    }

    // ── View Profile ──────────────────────────────────────────────────────────

    #[Test]
    public function authenticated_user_can_view_own_profile(): void
    {
        [$user, $token] = $this->makeUser();
        $resp = $this->getJson('/api/v1/me/profile', $this->auth($token))->assertStatus(200);

        $this->assertEquals($user->email, $resp->json('email'));
    }

    #[Test]
    public function profile_response_does_not_expose_password(): void
    {
        [, $token] = $this->makeUser();
        $response = $this->getJson('/api/v1/me/profile', $this->auth($token))->assertStatus(200);

        $body = $response->content();
        $this->assertStringNotContainsString('$2y$', $body);
        $this->assertStringNotContainsString('"password"', $body);
    }

    #[Test]
    public function profile_response_does_not_expose_remember_token(): void
    {
        [, $token] = $this->makeUser();
        $response = $this->getJson('/api/v1/me/profile', $this->auth($token));

        $this->assertArrayNotHasKey('remember_token', $response->json());
    }

    // ── Update Profile ────────────────────────────────────────────────────────

    #[Test]
    public function user_can_update_own_name(): void
    {
        [$user, $token] = $this->makeUser();

        $this->putJson('/api/v1/me/profile', ['name' => 'Tên Mới'], $this->auth($token))
            ->assertStatus(200);

        $this->assertEquals('Tên Mới', $user->fresh()->name);
    }

    #[Test]
    public function user_cannot_escalate_role_via_profile_update(): void
    {
        [$user, $token] = $this->makeUser(['user_type' => RoleType::STUDENT]);

        // Cố gán user_type thành ADMIN
        $this->putJson('/api/v1/me/profile', [
            'name' => 'Hacker',
            'user_type' => RoleType::ADMIN->value,
        ], $this->auth($token))->assertStatus(200);

        // user_type phải không đổi
        $this->assertEquals(RoleType::STUDENT, $user->fresh()->user_type);
    }

    #[Test]
    public function user_cannot_change_email_to_taken_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        [, $token] = $this->makeUser();

        $this->putJson('/api/v1/me/profile', ['email' => 'taken@example.com'], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function user_cannot_change_own_id(): void
    {
        [$user, $token] = $this->makeUser();
        $originalId = $user->id;

        $this->putJson('/api/v1/me/profile', ['id' => 9999], $this->auth($token))->assertStatus(200);

        $this->assertEquals($originalId, $user->fresh()->id);
    }

    // ── Password Update ───────────────────────────────────────────────────────

    #[Test]
    public function user_can_change_password_with_correct_current_password(): void
    {
        [$user, $token] = $this->makeUser(['password' => 'OldPassword1!']);

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'OldPassword1!',
            'password' => 'NewPassword2@',
            'password_confirmation' => 'NewPassword2@',
        ], $this->auth($token))->assertSuccessful();

        $this->assertTrue(Hash::check('NewPassword2@', $user->fresh()->password));
    }

    #[Test]
    public function user_cannot_change_password_with_wrong_current_password(): void
    {
        [, $token] = $this->makeUser(['password' => 'Correct1!']);

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'WrongPassword!',
            'password' => 'NewPassword2@',
            'password_confirmation' => 'NewPassword2@',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function password_update_requires_confirmation_match(): void
    {
        [, $token] = $this->makeUser(['password' => 'OldPassword1!']);

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'OldPassword1!',
            'password' => 'NewPassword2@',
            'password_confirmation' => 'DifferentPassword!',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function new_password_minimum_8_chars(): void
    {
        [, $token] = $this->makeUser(['password' => 'OldPassword1!']);

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'OldPassword1!',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ], $this->auth($token))->assertStatus(422);
    }

    // ── Avatar Upload ─────────────────────────────────────────────────────────

    #[Test]
    public function user_can_upload_valid_avatar(): void
    {
        Storage::fake('public');
        [, $token] = $this->makeUser();

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $this->post('/api/v1/me/avatar', ['avatar' => $file], $this->auth($token))
            ->assertSuccessful();
    }

    #[Test]
    public function avatar_upload_with_php_file_is_rejected(): void
    {
        Storage::fake('public');
        [, $token] = $this->makeUser();

        $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $this->post('/api/v1/me/avatar', ['avatar' => $file], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function avatar_upload_without_file_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->post('/api/v1/me/avatar', [], $this->auth($token))->assertStatus(422);
    }

    // ── Data Isolation ────────────────────────────────────────────────────────

    #[Test]
    public function user_a_profile_update_does_not_affect_user_b(): void
    {
        [$userA, $tokenA] = $this->makeUser(['email' => 'usera@ex.com']);
        $userB = User::factory()->create(['email' => 'userb@ex.com', 'name' => 'User B Original']);

        $this->putJson('/api/v1/me/profile', ['name' => 'User A Updated'], $this->auth($tokenA));

        $this->assertEquals('User B Original', $userB->fresh()->name);
    }
}
