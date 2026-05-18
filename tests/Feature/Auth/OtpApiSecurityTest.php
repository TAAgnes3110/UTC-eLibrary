<?php

namespace Tests\Feature\Auth;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * OTP / resend-otp — bảo mật response, đầu vào, không lộ mã trong API.
 */
class OtpApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function resend_otp_success_response_must_not_include_otp_in_json(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => 'otp-leak-test@example.com',
            'name' => 'Tester',
        ]);

        $response->assertSuccessful();
        $this->assertArrayNotHasKey('otp', $response->json(), 'API không được trả mã OTP trong body — chỉ gửi qua email.');
    }

    #[Test]
    public function resend_otp_with_invalid_email_returns_422(): void
    {
        $this->postJson('/api/v1/auth/resend-otp', [
            'email' => 'not-an-email',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function resend_otp_with_empty_email_returns_422(): void
    {
        $this->postJson('/api/v1/auth/resend-otp', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function resend_otp_with_array_email_returns_422(): void
    {
        $this->postJson('/api/v1/auth/resend-otp', [
            'email' => ['nested' => 'hack@example.com'],
        ])->assertStatus(422);
    }

    #[Test]
    public function resend_otp_stores_otp_in_database_not_only_in_response(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/resend-otp', [
            'email' => 'db-otp@example.com',
            'name' => 'Tester',
        ])->assertSuccessful();

        $this->assertDatabaseHas('email_otp', ['email' => 'db-otp@example.com']);
        $record = EmailOtp::query()->where('email', 'db-otp@example.com')->first();
        $this->assertNotNull($record);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $record->otp);
    }

    #[Test]
    public function verify_otp_with_sql_injection_in_email_returns_422_or_400(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => "' OR 1=1 --",
            'otp' => '123456',
        ]);

        $this->assertContains($response->status(), [400, 422]);
    }

    #[Test]
    public function reset_password_response_must_not_include_password_hash(): void
    {
        $user = User::factory()->create(['email' => 'hash-leak@example.com']);
        $originalHash = $user->password;

        Mail::fake();
        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user->email, 'name' => 'U']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'otp' => '000000',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertStatus(400);

        $this->assertStringNotContainsString($originalHash, $response->content());
        $this->assertStringNotContainsString('$2y$', $response->content());
    }
}
