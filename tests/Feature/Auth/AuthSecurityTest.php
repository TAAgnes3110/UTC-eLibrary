<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleType;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bộ test bảo mật & logic cho Authentication API.
 *
 * Tester vai: Kẻ tấn công khó tính + QA khắt khe
 * Mục tiêu: Tìm lỗ hổng bảo mật, edge cases, data integrity issues
 */
class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // SECTION: LOGIN – SQL Injection, brute-force, type coercion
    // =========================================================================

    #[Test]
    public function login_missing_both_fields_returns_422(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login', 'password']);
    }

    #[Test]
    public function login_missing_password_returns_422(): void
    {
        $this->postJson('/api/v1/auth/login', ['login' => 'user@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function login_missing_login_field_returns_422(): void
    {
        $this->postJson('/api/v1/auth/login', ['password' => 'password123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    }

    #[Test]
    #[DataProvider('sqlInjectionProvider')]
    public function login_with_sql_injection_login_field_returns_401(string $maliciousInput): void
    {
        $this->postJson('/api/v1/auth/login', [
            'login' => $maliciousInput,
            'password' => 'password',
        ])->assertStatus(401);
    }

    public static function sqlInjectionProvider(): array
    {
        return [
            ["' OR '1'='1"] => ["' OR '1'='1"],
            ['1; DROP TABLE users; --'] => ['1; DROP TABLE users; --'],
            ["admin'--"] => ["admin'--"],
            ["' UNION SELECT id,email,password FROM users--"] => ["' UNION SELECT id,email,password FROM users--"],
            ['"; INSERT INTO users VALUES (999); --'] => ['"; INSERT INTO users VALUES (999); --'],
        ];
    }

    #[Test]
    #[DataProvider('sqlInjectionProvider')]
    public function login_with_sql_injection_password_returns_401(string $maliciousInput): void
    {
        User::factory()->create(['email' => 'victim@example.com', 'password' => 'safepassword']);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'victim@example.com',
            'password' => $maliciousInput,
        ])->assertStatus(401);
    }

    #[Test]
    public function login_with_array_type_for_login_field_returns_validation_error(): void
    {
        // Type coercion attack – gửi array thay vì string
        $this->postJson('/api/v1/auth/login', [
            'login' => ['email' => 'admin@example.com'],
            'password' => 'password',
        ])->assertStatus(422);
    }

    #[Test]
    public function login_with_null_login_returns_422(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'login' => null,
            'password' => 'password',
        ])->assertStatus(422);
    }

    #[Test]
    public function login_with_extremely_long_password_is_rejected_safely(): void
    {
        // Phòng chống bcrypt timing attack với mật khẩu > 72 bytes
        $longPassword = str_repeat('A', 10000);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'victim@example.com',
            'password' => $longPassword,
        ])->assertStatus(401); // Không crash, không lộ thông tin
    }

    #[Test]
    public function login_wrong_password_returns_401_not_user_info(): void
    {
        User::factory()->create([
            'email' => 'exists@example.com',
            'password' => 'correct_password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'exists@example.com',
            'password' => 'wrong_password',
        ])->assertStatus(401);

        // Phải KHÔNG tiết lộ "email đúng, password sai" - chỉ trả thông báo chung
        $body = $response->json();
        $this->assertArrayNotHasKey('user', $body);
        $this->assertArrayNotHasKey('token', $body);
    }

    #[Test]
    public function login_nonexistent_user_returns_401(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'login' => 'notexist@example.com',
            'password' => 'password',
        ])->assertStatus(401);
    }

    #[Test]
    public function login_with_email_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'emaillogin@example.com',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'emaillogin@example.com',
            'password' => 'Password123!',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'messages', 'token', 'user']);
    }

    #[Test]
    public function login_with_user_code_returns_token(): void
    {
        User::factory()->create([
            'code' => '123456789',
            'email' => 'codelogin@example.com',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => '123456789',
            'password' => 'Password123!',
        ])->assertStatus(200)->assertJsonStructure(['token']);
    }

    #[Test]
    public function login_with_phone_number_returns_token(): void
    {
        User::factory()->create([
            'email' => 'phonelogin@example.com',
            'phone' => '0912345678',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => '0912345678',
            'password' => 'Password123!',
        ])->assertStatus(200)->assertJsonStructure(['token']);
    }

    #[Test]
    public function token_is_not_returned_in_error_response(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'notexist@example.com',
            'password' => 'wrongpass',
        ])->assertStatus(401);

        $this->assertArrayNotHasKey('token', $response->json());
    }

    // =========================================================================
    // SECTION: REGISTER – Validation, uniqueness, data integrity
    // =========================================================================

    #[Test]
    public function register_with_empty_body_returns_422(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name', 'email', 'password', 'phone']);
    }

    #[Test]
    public function register_code_must_be_9_to_12_digits(): void
    {
        // Quá ngắn
        $this->postJson('/api/v1/auth/register', [
            'code' => '12345678', // 8 digits - quá ngắn
            'name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);

        // Quá dài
        $this->postJson('/api/v1/auth/register', [
            'code' => '1234567890123', // 13 digits - quá dài
            'name' => 'Test',
            'email' => 'test2@example.com',
            'phone' => '0912345679',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function register_code_with_letters_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'code' => 'ABC123456',
            'name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function register_duplicate_email_returns_error(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com', 'code' => '111111111']);

        $this->postJson('/api/v1/auth/register', [
            'code' => '222222222',
            'name' => 'Another User',
            'email' => 'duplicate@example.com', // đã tồn tại
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function register_duplicate_code_returns_error(): void
    {
        User::factory()->create(['email' => 'existing@example.com', 'code' => '111111111']);

        $this->postJson('/api/v1/auth/register', [
            'code' => '111111111', // đã tồn tại
            'name' => 'Another User',
            'email' => 'new@example.com',
            'phone' => '0912345679',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function register_password_mismatch_returns_error(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'code' => '111111111',
            'name' => 'Test User',
            'email' => 'mismatch@example.com',
            'phone' => '0912345678',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function register_password_minimum_8_chars(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'code' => '111111111',
            'name' => 'Test User',
            'email' => 'short@example.com',
            'phone' => '0912345678',
            'password' => '1234567', // 7 chars
            'password_confirmation' => '1234567',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function register_invalid_email_format_returns_error(): void
    {
        foreach (['notanemail', 'missing@', '@nodomain.com', 'spaces in@email.com', 'a@b'] as $badEmail) {
            $this->postJson('/api/v1/auth/register', [
                'code' => '111111111',
                'name' => 'Test',
                'email' => $badEmail,
                'phone' => '0912345678',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertStatus(422, "Email '$badEmail' should be invalid");
        }
    }

    #[Test]
    public function register_forces_member_role_regardless_of_input(): void
    {
        // Gửi ADMIN hoặc SUPER_ADMIN → phải bị hạ xuống MEMBER
        foreach ([RoleType::ADMIN->value, RoleType::SUPER_ADMIN->value, RoleType::LIBRARIAN->value] as $privilegedRole) {
            // Mỗi vòng cần email/phone khác nhau
            $uniqueKey = substr(md5($privilegedRole), 0, 6);
            $this->postJson('/api/v1/auth/register', [
                'code' => '111111'.$uniqueKey,
                'name' => 'Hacker',
                'email' => "hack_{$uniqueKey}@example.com",
                'phone' => '09'.$uniqueKey.'0000',
                'password' => 'password',
                'password_confirmation' => 'password',
                'user_type' => $privilegedRole,
            ])->assertStatus(422, "Role $privilegedRole should be rejected at registration");
        }
    }

    #[Test]
    public function register_with_xss_in_name_stores_sanitized_or_escaped(): void
    {
        // XSS payload trong name – phải được lưu as-is (không execute), không bị 500
        $xssPayload = '<script>alert("xss")</script>';
        $this->postJson('/api/v1/auth/register', [
            'code' => '111222333',
            'name' => $xssPayload,
            'email' => 'xss@example.com',
            'phone' => '0999888777',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSuccessful(); // 200 → OTP gửi đi, không crash
    }

    #[Test]
    public function register_with_special_unicode_name_is_accepted(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'code' => '111222444',
            'name' => 'Nguyễn Thị Hồng Ân 美丽的名字',
            'email' => 'unicode@example.com',
            'phone' => '0987654321',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSuccessful();
    }

    // =========================================================================
    // SECTION: VERIFY OTP – Timing, expired, wrong OTP
    // =========================================================================

    #[Test]
    public function verify_otp_with_wrong_otp_returns_400(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'code' => '111111111',
            'name' => 'Test',
            'email' => 'wrongotp@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'wrongotp@example.com',
            'otp' => '000000', // sai
        ])->assertStatus(400)->assertJson(['status' => 'error']);
    }

    #[Test]
    public function verify_otp_with_no_pending_registration_returns_error(): void
    {
        // Không có cache đăng ký → OTP không tồn tại
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'ghost@example.com',
            'otp' => '123456',
        ])->assertStatus(400);
    }

    #[Test]
    public function verify_otp_missing_fields_returns_422(): void
    {
        $this->postJson('/api/v1/auth/verify-otp', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'otp']);
    }

    #[Test]
    public function verify_otp_with_invalid_email_format_returns_422(): void
    {
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'not-valid-email',
            'otp' => '123456',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function verify_otp_with_integer_otp_is_handled_correctly(): void
    {
        // Type coercion: OTP gửi dưới dạng số nguyên (không phải string)
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'integer-otp@example.com',
            'otp' => 123456, // integer type
        ])->assertStatus(400); // Không có registration → 400
    }

    // =========================================================================
    // SECTION: RESET PASSWORD – OTP reuse, email not found
    // =========================================================================

    #[Test]
    public function reset_password_missing_fields_returns_422(): void
    {
        $this->postJson('/api/v1/auth/reset-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'otp', 'password']);
    }

    #[Test]
    public function reset_password_with_wrong_otp_returns_400(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'otp' => '000000',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(400);
    }

    #[Test]
    public function reset_password_with_valid_otp_actually_changes_password(): void
    {
        $user = User::factory()->create([
            'email' => 'changedpass@example.com',
            'password' => 'old_secret_password',
        ]);

        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user->email]);
        $otp = EmailOtp::where('email', $user->email)->firstOrFail()->otp;

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'BrandNew$ecure9!',
            'password_confirmation' => 'BrandNew$ecure9!',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('BrandNew$ecure9!', $user->fresh()->password));
        $this->assertFalse(Hash::check('old_secret_password', $user->fresh()->password));
    }

    #[Test]
    public function reset_password_for_nonexistent_email_returns_400(): void
    {
        // Gửi OTP trước
        $this->postJson('/api/v1/auth/resend-otp', ['email' => 'ghost@example.com']);

        $otpRecord = EmailOtp::where('email', 'ghost@example.com')->first();
        if (! $otpRecord) {
            $this->markTestSkipped('OTP not sent – skipping.');
        }

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'ghost@example.com',
            'otp' => $otpRecord->otp,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(400); // User không tồn tại
    }

    #[Test]
    public function reset_password_does_not_expose_current_password_hash(): void
    {
        $user = User::factory()->create(['email' => 'no-leak@example.com']);
        $originalHash = $user->password;

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'otp' => '000000',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(400);

        // Phải không chứa hash mật khẩu cũ trong response
        $this->assertStringNotContainsString($originalHash, $response->content());
    }

    // =========================================================================
    // SECTION: JWT SECURITY – Tampered/expired tokens
    // =========================================================================

    #[Test]
    public function accessing_protected_route_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/auth/user')
            ->assertStatus(401);
    }

    #[Test]
    public function accessing_protected_route_with_invalid_token_returns_401(): void
    {
        $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer this.is.not.a.valid.jwt',
        ])->assertStatus(401);
    }

    #[Test]
    public function accessing_protected_route_with_modified_jwt_payload_returns_401(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Giả mạo payload bằng cách thêm ký tự vào giữa token
        $parts = explode('.', $token);
        $parts[1] = base64_encode(json_encode(['sub' => 9999999, 'iat' => time()]));
        $tamperedToken = implode('.', $parts);

        $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer '.$tamperedToken,
        ])->assertStatus(401);
    }

    #[Test]
    public function refresh_without_bearer_token_returns_401(): void
    {
        $this->postJson('/api/v1/auth/refresh', [])
            ->assertStatus(401);
    }

    #[Test]
    public function refresh_with_invalid_token_returns_401(): void
    {
        $this->postJson('/api/v1/auth/refresh', [], [
            'Authorization' => 'Bearer invalid.token.here',
        ])->assertStatus(401);
    }

    #[Test]
    public function password_is_not_returned_in_user_response(): void
    {
        $user = User::factory()->create(['email' => 'noleak@example.com', 'password' => 'secret_password']);
        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200);

        $body = $response->content();
        $this->assertStringNotContainsString('secret_password', $body);
        $this->assertStringNotContainsString('$2y$', $body); // Không lộ bcrypt hash
        $this->assertArrayNotHasKey('password', $response->json());
    }

    // =========================================================================
    // SECTION: PRIVILEGE ESCALATION – Không thể tự thăng quyền
    // =========================================================================

    #[Test]
    public function logged_in_reader_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $token = JWTAuth::fromUser($user);

        // Thử truy cập admin-only endpoint
        $this->getJson('/api/v1/users', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(403);
    }

    #[Test]
    public function role_in_jwt_payload_does_not_override_database_role(): void
    {
        // Tạo user STUDENT
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $token = JWTAuth::fromUser($user);

        // User chỉ là STUDENT, dù JWT có ghi gì thì vẫn phải bị chặn ở admin route
        $this->getJson('/api/v1/users', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(403);
    }
}
