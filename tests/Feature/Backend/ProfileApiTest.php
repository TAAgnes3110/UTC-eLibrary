<?php

namespace Tests\Feature\Backend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_me_profile_show_returns_200_with_token(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/profile', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    public function test_me_profile_update_returns_200_with_valid_payload(): void
    {
        [$user, $token] = $this->createUserAndToken([
            'email' => 'profile-old@example.com',
        ]);

        $response = $this->putJson('/api/v1/me/profile', [
            'name' => 'Profile Updated',
            'email' => 'profile-new@example.com',
            'phone' => '0901234567',
            'date_of_birth' => '2000-01-20',
            'gender' => 'female',
            'address' => 'Ha Noi',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);

        $fresh = $user->fresh();
        $this->assertSame('Profile Updated', $fresh->name);
        $this->assertSame('profile-new@example.com', $fresh->email);
        $this->assertSame('0901234567', $fresh->phone);
        $this->assertSame('2000-01-20', $fresh->date_of_birth?->format('Y-m-d'));
        $this->assertSame('female', $fresh->gender);
        $this->assertSame('Ha Noi', $fresh->address);
    }

    public function test_me_profile_staff_can_update_code(): void
    {
        [$admin, $token] = $this->createAdminUserAndToken([
            'code' => '001000000099',
        ]);

        $response = $this->putJson('/api/v1/me/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'date_of_birth' => null,
            'gender' => null,
            'address' => null,
            'code' => '001000000088',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonPath('data.code', '001000000088');
        $this->assertSame('001000000088', User::query()->find($admin->id)->code);
    }

    public function test_me_password_update_returns_200_with_valid_payload(): void
    {
        [$user, $token] = $this->createUserAndToken([
            'password' => 'old-password',
        ]);

        $response = $this->putJson('/api/v1/me/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_me_profile_update_rejects_current_year_date_of_birth(): void
    {
        [, $token] = $this->createUserAndToken();

        $currentYear = (int) now()->format('Y');

        $this->putJson('/api/v1/me/profile', [
            'name' => 'Test User',
            'email' => 'dob-test@example.com',
            'phone' => '0901111222',
            'date_of_birth' => "{$currentYear}-01-15",
            'gender' => 'male',
            'address' => null,
        ], $this->apiTokenHeaders($token))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_me_password_update_returns_422_when_current_password_invalid(): void
    {
        [, $token] = $this->createUserAndToken([
            'password' => 'old-password',
        ]);

        $response = $this->putJson('/api/v1/me/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(422)->assertJsonPath('status', 'error');
    }
}
