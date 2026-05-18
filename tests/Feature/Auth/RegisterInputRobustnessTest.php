<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterInputRobustnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Customer::create([
            'name' => 'SV Test',
            'code' => '012345678912',
            'status' => 1,
        ]);
    }

    #[Test]
    public function register_stores_xss_name_without_server_side_strip(): void
    {
        $xss = '<img src=x onerror=alert(1)>';

        $this->postJson('/api/v1/auth/register', [
            'code' => '012345678912',
            'name' => $xss,
            'email' => 'xss-reg@example.com',
            'phone' => '0911111111',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertSuccessful();

        $this->assertDatabaseHas('email_otp', ['email' => 'xss-reg@example.com']);
    }

    #[Test]
    public function register_with_extremely_long_name_returns_422(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'code' => '012345678912',
            'name' => str_repeat('A', 500),
            'email' => 'longname@example.com',
            'phone' => '0922222222',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertStatus(422);
    }

    #[Test]
    public function register_with_null_bytes_in_email_returns_422(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'code' => '012345678912',
            'name' => 'Test',
            'email' => "test\0@example.com",
            'phone' => '0933333333',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertStatus(422);
    }

    #[Test]
    public function register_with_existing_phone_returns_422(): void
    {
        User::factory()->create(['phone' => '0944444444', 'email' => 'other@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'code' => '099999999999',
            'name' => 'Dup Phone',
            'email' => 'newphone@example.com',
            'phone' => '0944444444',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }
}
