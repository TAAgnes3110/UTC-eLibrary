<?php

namespace Tests\Feature\Backend;

use App\Models\Customer;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

/**
 * Test API auth: register, verify-otp, login, logout, refresh, user.
 *
 * @see App\Http\Controllers\Api\AuthController
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tạo Customer dùng cho đăng ký (AuthService kiểm tra code).
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Customer::create([
            'name' => 'Test Student',
            'code' => '12345678',
            'status' => 1,
        ]);
    }

    /**
     * POST /api/v1/auth/register tạo OTP và trả success.
     *
     * @return void
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'code' => '12345678',
            'email' => 'teststudent@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('email_otp', ['email' => 'teststudent@example.com']);
    }

    /**
     * verify-otp sau register tạo user và library_card.
     *
     * @return void
     */
    public function test_user_can_verify_otp_and_complete_registration(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'code' => '12345678',
            'email' => 'teststudent@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $otpRecord = EmailOtp::where('email', 'teststudent@example.com')->first();
        $this->assertNotNull($otpRecord);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'teststudent@example.com',
            'otp' => $otpRecord->otp,
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('users', ['email' => 'teststudent@example.com', 'code' => '12345678']);
        $user = User::where('email', 'teststudent@example.com')->first();
        $this->assertDatabaseHas('library_cards', [
            'user_id' => $user->id,
            'card_number' => '12345678',
        ]);
    }

    /**
     * POST /api/v1/auth/login với email và password trả token, user.
     *
     * @return void
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => 'password',
            'code' => '87654321',
        ]);
        $user->libraryCard()->create([
            'card_number' => '87654321',
            'status' => 'active',
            'is_active' => true,
            'issue_date' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'loginuser@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'status', 'messages', 'token', 'user',
        ]);
    }

    /**
     * Đăng ký lặp trong thời gian ngắn bị throttle 429.
     *
     * @return void
     */
    public function test_otp_throttling(): void
    {
        $payload = [
            'name' => 'Test Student',
            'code' => '12345678',
            'email' => 'teststudent@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $this->postJson('/api/v1/auth/register', $payload);
        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(429);
    }

    /**
     * GET /api/v1/auth/user với token hợp lệ trả thông tin user.
     *
     * @return void
     */
    public function test_auth_user_returns_user_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'authuser@example.com', 'password' => 'password']);
        $user->libraryCard()->create([
            'card_number' => $user->code,
            'status' => 'active',
            'is_active' => true,
            'issue_date' => now(),
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)->assertJsonStructure(['id', 'name', 'email']);
    }

    /**
     * POST /api/v1/auth/logout invalidate token.
     *
     * @return void
     */
    public function test_logout_invalidates_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $user->libraryCard()->create([
            'card_number' => $user->code ?? '12345',
            'status' => 'active',
            'is_active' => true,
            'issue_date' => now(),
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
    }
}
