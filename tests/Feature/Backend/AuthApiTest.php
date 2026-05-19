<?php

namespace Tests\Feature\Backend;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Models\Customer;
use App\Models\EmailOtp;
use App\Models\LibraryCard;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Feature\Backend\ActsAsApiUser;
use Tests\TestCase;

/**
 * Test API auth: register, verify-otp, login, logout, refresh, user.
 *
 * @see App\Http\Controllers\Api\AuthController
 */
class AuthApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * Tạo Customer dùng cho đăng ký (AuthService kiểm tra code).
     */
    protected function setUp(): void
    {
        parent::setUp();
        Customer::create([
            'name' => 'Test Student',
            'code' => '012345678912',
            'status' => 1,
        ]);
    }

    /**
     * POST /api/v1/auth/register tạo OTP và trả success.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'code' => '012345678912',
            'email' => 'teststudent@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('email_otp', ['email' => 'teststudent@example.com']);
    }

    /**
     * verify-otp sau register tạo user
     */
    public function test_user_can_verify_otp_and_complete_registration(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'code' => '012345678912',
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
        $this->assertDatabaseHas('users', ['email' => 'teststudent@example.com', 'code' => '012345678912']);
        $user = User::where('email', 'teststudent@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(RoleType::MEMBER->value, $user->user_type->value);
        $this->assertDatabaseMissing('library_cards', [
            'user_id' => $user->id,
        ]);
    }

    public function test_register_forces_member_even_if_user_sends_student_type(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'code' => '012345678912',
            'email' => 'memberforced@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
            'user_type' => RoleType::STUDENT->value,
        ])->assertStatus(200);

        $otpRecord = EmailOtp::where('email', 'memberforced@example.com')->firstOrFail();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'memberforced@example.com',
            'otp' => $otpRecord->otp,
        ])->assertStatus(200);

        $user = User::where('email', 'memberforced@example.com')->firstOrFail();
        $this->assertSame(RoleType::MEMBER->value, $user->user_type->value);
    }

    /**
     * verify-otp: thẻ khách (user_id null) trùng mã định danh với user mới → gắn user_id.
     */
    public function test_verify_registration_links_orphan_library_card_with_matching_code(): void
    {
        $code = '012345679001';

        LibraryCard::query()->create([
            'user_id' => null,
            'card_number' => $code,
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'code' => $code,
            'full_name' => 'Khách trước đăng ký',
            'email' => 'orphan-card@example.com',
            'phone' => '0900000001',
            'address' => 'Hà Nội',
            'date_of_birth' => '1990-01-01',
            'photo_path' => 'photos/orphan.jpg',
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Người mới',
            'code' => $code,
            'email' => 'linked-user@example.com',
            'phone' => '0900000002',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(200);

        $otpRecord = EmailOtp::where('email', 'linked-user@example.com')->firstOrFail();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'linked-user@example.com',
            'otp' => $otpRecord->otp,
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        $user = User::where('email', 'linked-user@example.com')->firstOrFail();
        $this->assertDatabaseHas('library_cards', [
            'code' => $code,
            'user_id' => $user->id,
        ]);
        $this->assertNull(
            app(LibraryCardManagementService::class)->linkOrphanGuestCardToNewUser($user->fresh()),
            'Đã có thẻ gắn user thì không liên kết thêm.'
        );
    }

    /**
     * POST /api/v1/auth/login với email và password trả token, user.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => 'password',
            'code' => '098765432109',
        ]);
        $user->libraryCard()->create([
            'card_number' => 'UTC-LOGIN-TEST',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'is_active' => true,
            'issue_date' => now()->toDateString(),
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
     */
    public function test_otp_throttling(): void
    {
        $payload = [
            'name' => 'Test Student',
            'code' => '012345678912',
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
     */
    public function test_auth_user_returns_user_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'authuser@example.com', 'password' => 'password']);
        $user->libraryCard()->create([
            'card_number' => 'UTC-AUTH-USER',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'is_active' => true,
            'issue_date' => now()->toDateString(),
            'code' => $user->code,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200)->assertJsonStructure(['id', 'name', 'email']);
    }

    /**
     * POST /api/v1/auth/logout invalidate token.
     */
    public function test_logout_invalidates_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $user->libraryCard()->create([
            'card_number' => 'UTC-LOGOUT-TEST',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'is_active' => true,
            'issue_date' => now()->toDateString(),
            'code' => $user->code,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
    }

    /**
     * POST /api/v1/auth/resend-otp gửi lại OTP thành công.
     */
    public function test_user_can_resend_otp(): void
    {
        $email = 'resend-otp@example.com';

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => $email,
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('email_otp', ['email' => $email]);
    }

    /**
     * POST /api/v1/auth/reset-password đổi mật khẩu thành công với OTP hợp lệ.
     */
    public function test_user_can_reset_password_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'resetpass@example.com',
            'password' => 'oldpassword',
        ]);

        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user->email])->assertStatus(200);
        $otp = EmailOtp::where('email', $user->email)->firstOrFail()->otp;

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    /**
     * POST /api/v1/auth/refresh với token hợp lệ trả token mới.
     */
    public function test_refresh_returns_new_token_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'refresh@example.com', 'password' => 'password']);
        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/v1/auth/refresh', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'status', 'messages', 'token', 'expires_in',
        ]);
    }

    /**
     * SPA Inertia: session web (không Bearer) vẫn gọi được API sau statefulApi.
     */
    public function test_api_accepts_web_session_without_bearer(): void
    {
        $user = User::factory()->create([
            'email' => 'session-api@example.com',
            'user_type' => RoleType::LIBRARIAN,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('Origin', config('app.url'))
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)->assertJsonStructure(['id', 'name', 'email']);
    }

    public function test_api_invalid_bearer_still_authenticates_via_web_session(): void
    {
        $user = User::factory()->create([
            'email' => 'session-bad-jwt@example.com',
            'user_type' => RoleType::LIBRARIAN,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('Origin', config('app.url'))
            ->withHeader('Authorization', 'Bearer invalid.token.value')
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)->assertJsonPath('email', 'session-bad-jwt@example.com');
    }

    public function test_session_token_returns_jwt_for_web_session(): void
    {
        $user = User::factory()->create([
            'email' => 'session-token@example.com',
            'user_type' => RoleType::LIBRARIAN,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('Origin', config('app.url'))
            ->postJson('/api/v1/auth/session-token');

        $response->assertStatus(200)->assertJsonStructure([
            'status', 'messages', 'token', 'expires_in',
        ]);
    }

    public function test_post_books_accepts_web_session_without_bearer(): void
    {
        [$user] = $this->createAdminUserAndToken();
        $now = now();
        $cid = DB::table('classifications')->insertGetId([
            'code' => 'TST', 'name' => 'Test', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $wid = DB::table('warehouses')->insertGetId([
            'code' => 'W1', 'name' => 'Kho', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->actingAs($user)
            ->withHeader('Origin', config('app.url'))
            ->postJson('/api/v1/books', [
                'title' => 'Sách session SPA',
                'classification_id' => $cid,
                'warehouse_id' => $wid,
                'quantity' => 0,
                'resource_type' => 'digital',
                'access_mode' => 'online_only',
            ])
            ->assertStatus(201);
    }
}
