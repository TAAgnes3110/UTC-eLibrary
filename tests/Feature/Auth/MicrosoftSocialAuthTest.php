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
            'name' => 'Reader Outlook',
            'user_type' => RoleType::MEMBER->value,
        ]);
    }

    public function test_microsoft_login_creates_member_for_st_utc_email(): void
    {
        $this->mockMicrosoftUser(
            '223630694@st.utc.edu.vn',
            'SV UTC',
            'ms-utc-1',
            raw: [
                'id' => 'ms-utc-1',
                'displayName' => 'SV UTC',
                'mail' => '223630694@st.utc.edu.vn',
                'userPrincipalName' => '223630694@st.utc.edu.vn',
            ]
        );

        $this->get('/auth/microsoft/callback')->assertRedirect(route('reader.home'));

        $user = User::query()->where('email', '223630694@st.utc.edu.vn')->first();
        $this->assertNotNull($user);
        $this->assertSame(RoleType::MEMBER->value, $user->user_type instanceof RoleType ? $user->user_type->value : $user->user_type);
        $this->assertSame('223630694', $user->code);
        $this->assertNull($user->cohort);
    }

    public function test_microsoft_login_prefers_mail_over_user_principal_name(): void
    {
        $this->mockMicrosoftUser(
            'user_outlook.com#EXT#@tenant.onmicrosoft.com',
            'Guest User',
            'ms-guest-1',
            raw: [
                'id' => 'ms-guest-1',
                'displayName' => 'Guest User',
                'mail' => 'guest.user@outlook.com',
                'userPrincipalName' => 'user_outlook.com#EXT#@tenant.onmicrosoft.com',
            ]
        );

        $this->get('/auth/microsoft/callback')->assertRedirect(route('reader.home'));

        $this->assertDatabaseHas('users', [
            'email' => 'guest.user@outlook.com',
            'user_type' => RoleType::MEMBER->value,
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'user_outlook.com#EXT#@tenant.onmicrosoft.com',
        ]);
    }

    public function test_microsoft_login_without_email_redirects_to_login(): void
    {
        $this->mockMicrosoftUser('', 'No Email', 'ms-no-email');

        $this->get('/auth/microsoft/callback')
            ->assertRedirect(route('login'));
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    private function mockMicrosoftUser(string $email, string $name, string $id, ?array $raw = null): void
    {
        $raw ??= [
            'id' => $id,
            'displayName' => $name,
            'mail' => $email,
            'userPrincipalName' => $email,
        ];

        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getAvatar')->andReturn(null);
        $socialiteUser->shouldReceive('getRaw')->andReturn($raw);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('microsoft-azure')->andReturn($provider);
    }
}
