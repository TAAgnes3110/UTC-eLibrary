<?php

namespace Tests\Feature\Modules;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Auth & OTP (10 case).
 *
 * | # | Case | Mong đợi |
 * |---|------|----------|
 * | 1 | Login thiếu password | 422 |
 * | 2 | Login sai mật khẩu | 401, không có token |
 * | 3 | Login đúng | 200 + token |
 * | 4 | GET /auth/user không token | 401 |
 * | 5 | Resend OTP thành công | 200, KHÔNG có key `otp` |
 * | 6 | Verify OTP sai | 400 |
 * | 7 | Refresh không token | 401 |
 * | 8 | Student vào /users | 403 |
 * | 9 | Register body rỗng | 422 |
 * | 10 | GET /auth/user không lộ password | 200, không $2y$ |
 */
class AuthModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Customer::create(['name' => 'SV', 'code' => '012345678912', 'status' => 1]);
    }

    #[Test]
    public function case01_login_missing_password_returns_422(): void
    {
        $this->postJson('/api/v1/auth/login', ['login' => 'a@b.com'])
            ->assertStatus(422);
    }

    #[Test]
    public function case02_login_wrong_password_returns_401_without_token(): void
    {
        User::factory()->create(['email' => 'c02@example.com', 'password' => 'Secret123!']);
        $r = $this->postJson('/api/v1/auth/login', [
            'login' => 'c02@example.com', 'password' => 'wrong',
        ])->assertStatus(401);
        $this->assertArrayNotHasKey('token', $r->json());
    }

    #[Test]
    public function case03_login_valid_returns_200_with_token(): void
    {
        User::factory()->create(['email' => 'c03@example.com', 'password' => 'Secret123!']);
        $this->postJson('/api/v1/auth/login', [
            'login' => 'c03@example.com', 'password' => 'Secret123!',
        ])->assertStatus(200)->assertJsonStructure(['token']);
    }

    #[Test]
    public function case04_auth_user_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/auth/user')->assertStatus(401);
    }

    #[Test]
    public function case05_resend_otp_must_not_expose_otp_in_json(): void
    {
        Mail::fake();
        $r = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => 'c05@example.com', 'name' => 'T',
        ])->assertSuccessful();
        $this->assertArrayNotHasKey('otp', $r->json());
    }

    #[Test]
    public function case06_verify_otp_wrong_code_returns_400(): void
    {
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'ghost@c06.com', 'otp' => '000000',
        ])->assertStatus(400);
    }

    #[Test]
    public function case07_refresh_without_token_returns_401(): void
    {
        $this->postJson('/api/v1/auth/refresh')->assertStatus(401);
    }

    #[Test]
    public function case08_student_cannot_access_admin_users(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/users', $h)->assertStatus(403);
    }

    #[Test]
    public function case09_register_empty_body_returns_422(): void
    {
        $this->postJson('/api/v1/auth/register', [])->assertStatus(422);
    }

    #[Test]
    public function case10_auth_user_response_hides_password_hash(): void
    {
        $user = User::factory()->create(['email' => 'c10@example.com', 'password' => 'Secret123!']);
        $token = JWTAuth::fromUser($user);
        $r = $this->getJson('/api/v1/auth/user', $this->bearer($token))->assertStatus(200);
        $this->assertStringNotContainsString('$2y$', $r->content());
    }
}
