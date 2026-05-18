<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Profile /me (10 case).
 */
class ProfileModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    #[Test]
    public function case01_unauthenticated_profile_returns_401(): void
    {
        $this->getJson('/api/v1/me/profile')->assertStatus(401);
    }

    #[Test]
    public function case02_view_own_profile_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/profile', $h)->assertStatus(200);
    }

    #[Test]
    public function case03_profile_hides_password_field(): void
    {
        [, $h] = $this->studentContext();
        $r = $this->getJson('/api/v1/me/profile', $h);
        $this->assertStringNotContainsString('"password"', $r->content());
    }

    #[Test]
    public function case04_update_name_returns_200(): void
    {
        [$user, $h] = $this->studentContext();
        $this->putJson('/api/v1/me/profile', [
            'name' => 'Tên mới', 'email' => $user->email,
        ], $h)->assertStatus(200);
        $this->assertSame('Tên mới', $user->fresh()->name);
    }

    #[Test]
    public function case05_cannot_escalate_role_via_profile(): void
    {
        [$user, $h] = $this->studentContext();
        $this->putJson('/api/v1/me/profile', [
            'name' => $user->name, 'email' => $user->email,
            'user_type' => RoleType::ADMIN->value,
        ], $h)->assertStatus(200);
        $fresh = $user->fresh()->user_type;
        $this->assertSame(
            RoleType::STUDENT->value,
            $fresh instanceof RoleType ? $fresh->value : (string) $fresh
        );
    }

    #[Test]
    public function case06_update_without_name_returns_422(): void
    {
        [$user, $h] = $this->studentContext();
        $this->putJson('/api/v1/me/profile', ['email' => $user->email], $h)
            ->assertStatus(422);
    }

    #[Test]
    public function case07_duplicate_email_returns_422(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        [$user, $h] = $this->studentContext();
        $this->putJson('/api/v1/me/profile', [
            'name' => $user->name, 'email' => 'taken@example.com',
        ], $h)->assertStatus(422);
    }

    #[Test]
    public function case08_wrong_current_password_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->putJson('/api/v1/me/password', [
            'current_password' => 'wrong', 'password' => 'Newpass123!',
            'password_confirmation' => 'Newpass123!',
        ], $h)->assertStatus(422);
    }

    #[Test]
    public function case09_change_password_with_correct_current_succeeds(): void
    {
        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT, 'password' => 'Oldpass123!',
        ]);
        $token = JWTAuth::fromUser($user);
        $this->putJson('/api/v1/me/password', [
            'current_password' => 'Oldpass123!', 'password' => 'Newpass123!',
            'password_confirmation' => 'Newpass123!',
        ], $this->bearer($token))->assertSuccessful();
        $this->assertTrue(Hash::check('Newpass123!', $user->fresh()->password));
    }

    #[Test]
    public function case10_avatar_rejects_pdf_file(): void
    {
        Storage::fake('public');
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/avatar', [
            'avatar' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
        ], $h)->assertStatus(422);
    }
}
