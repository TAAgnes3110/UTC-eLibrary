<?php

namespace Tests\Feature\Backend;

use App\Models\Customer;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();
    Customer::create([
      'name' => 'Test Student',
      'code' => '12345678',
      'status' => 'active'
    ]);
  }

  public function test_user_can_register()
  {
    $response = $this->postJson('/api/auth/register', [
      'name' => 'Test Student',
      'code' => '12345678',
      'email' => 'teststudent@example.com',
      'phone' => '0912345678',
      'password' => 'password',
      'password_confirmation' => 'password',
    ]);

    $response->assertStatus(200)
      ->assertJson([
        'status' => 'success',
      ]);

    $this->assertDatabaseHas('email_otp', [
      'email' => 'teststudent@example.com'
    ]);
  }

  public function test_user_can_verify_otp_and_complete_registration()
  {
    // 1. Register first to generate OTP
    $response = $this->postJson('/api/auth/register', [
      'name' => 'Test Student',
      'code' => '12345678',
      'email' => 'teststudent@example.com',
      'phone' => '0912345678',
      'password' => 'password',
      'password_confirmation' => 'password',
    ]);

    // 2. Get OTP from DB
    $otpRecord = EmailOtp::where('email', 'teststudent@example.com')->first();
    $this->assertNotNull($otpRecord);
    $response = $this->postJson('/api/auth/verify-otp', [
      'email' => 'teststudent@example.com',
      'otp' => $otpRecord->otp
    ]);

    if ($response->status() !== 200) {
      $response->dump();
    }

    $response->assertStatus(200)
      ->assertJson([
        'status' => 'success',
      ]);
    $this->assertDatabaseHas('users', [
      'email' => 'teststudent@example.com',
      'code' => '12345678'
    ]);
    $user = User::where('email', 'teststudent@example.com')->first();
    $this->assertDatabaseHas('library_cards', [
      'user_id' => $user->id,
      'card_number' => '12345678'
    ]);
  }

  public function test_user_can_login()
  {
    $user = User::factory()->create([
      'email' => 'loginuser@example.com',
      'password' => bcrypt('password'),
      'code' => '87654321'
    ]);
    $user->libraryCard()->create([
      'card_number' => '87654321',
      'status' => 'active',
      'is_active' => true,
      'issue_date' => now(),
    ]);

    $response = $this->postJson('/api/auth/login', [
      'login' => 'loginuser@example.com',
      'password' => 'password'
    ]);

    $response->assertStatus(200)
      ->assertJsonStructure([
        'status',
        'messages',
        'token',
        'user'
      ]);
  }

  public function test_otp_throttling()
  {
    $this->postJson('/api/auth/register', [
      'name' => 'Test Student',
      'code' => '12345678',
      'email' => 'teststudent@example.com',
      'phone' => '0912345678',
      'password' => 'password',
      'password_confirmation' => 'password',
    ]);

    $response = $this->postJson('/api/auth/register', [
      'name' => 'Test Student',
      'code' => '12345678',
      'email' => 'teststudent@example.com',
      'phone' => '0912345678',
      'password' => 'password',
      'password_confirmation' => 'password',
    ]);

    // Expect 429 Too Many Requests because of throttling (90s)
    $response->assertStatus(429);
  }
}
