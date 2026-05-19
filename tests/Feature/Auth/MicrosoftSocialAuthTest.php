<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class MicrosoftSocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_microsoft_login_creates_member_for_external_email(): void
    {
        $this->mockMicrosoftUser('reader@outlook.com', 'Reader Outlook', 'ms-outlook-1');

        $response = $this->get('/auth/microsoft/callback');

        $response->assertRedirect(route('reader.home'));
        $this->assertDatabaseHas('users', [
            'email' => 'reader@outlook.com',
            'user_type' => RoleType::MEMBER->value,
        ]);
    }

    public function test_microsoft_login_creates_student_for_st_utc_email(): void
    {
        $this->mockMicrosoftUser('223630694@st.utc.edu.vn', 'SV UTC', 'ms-utc-1');

        $this->get('/auth/microsoft/callback')->assertRedirect(route('reader.home'));

        $user = User::query()->where('email', '223630694@st.utc.edu.vn')->first();
        $this->assertNotNull($user);
        $this->assertSame(RoleType::STUDENT->value, $user->user_type instanceof RoleType ? $user->user_type->value : $user->user_type);
        $this->assertSame('223630694', $user->code);
    }

    public function test_microsoft_login_without_email_redirects_to_login(): void
    {
        $this->mockMicrosoftUser('', 'No Email', 'ms-no-email');

        $this->get('/auth/microsoft/callback')
            ->assertRedirect(route('login'));
    }

    private function mockMicrosoftUser(string $email, string $name, string $id): void
    {
        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('microsoft-azure')->andReturn($provider);
    }
}
