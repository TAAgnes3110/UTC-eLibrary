<?php

namespace Tests\Feature\Profile;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileInputRobustnessTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'Accept' => 'application/json',
        ];
    }

    #[Test]
    public function profile_update_without_required_name_returns_422(): void
    {
        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'email' => 'noname@example.com',
        ]);

        $this->putJson('/api/v1/me/profile', [
            'email' => $user->email,
            'phone' => '0912345678',
        ], $this->auth($user))->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function profile_update_with_invalid_gender_returns_422(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $this->putJson('/api/v1/me/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => 'robot',
        ], $this->auth($user))->assertStatus(422)->assertJsonValidationErrors(['gender']);
    }

    #[Test]
    public function avatar_upload_rejects_non_image_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $file = UploadedFile::fake()->create('evil.pdf', 100, 'application/pdf');

        $this->postJson('/api/v1/me/avatar', [
            'avatar' => $file,
        ], $this->auth($user))->assertStatus(422);
    }

    #[Test]
    public function avatar_upload_rejects_oversized_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $file = UploadedFile::fake()->image('big.jpg')->size(6000);

        $this->postJson('/api/v1/me/avatar', [
            'avatar' => $file,
        ], $this->auth($user))->assertStatus(422);
    }

    #[Test]
    public function password_change_with_short_new_password_returns_422(): void
    {
        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'password' => 'OldPassword1!',
        ]);

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'OldPassword1!',
            'password' => 'short',
            'password_confirmation' => 'short',
        ], $this->auth($user))->assertStatus(422);
    }
}
