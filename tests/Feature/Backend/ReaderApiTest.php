<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_me_dashboard_requires_auth(): void
    {
        $this->getJson('/api/v1/me/dashboard')->assertStatus(401);
    }

    public function test_me_dashboard_returns_stats(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/dashboard', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => [
                'stats' => ['activeLoans', 'overdueCount', 'hasCard'],
            ],
        ]);
    }

    public function test_me_loans_requires_auth(): void
    {
        $this->getJson('/api/v1/me/loans')->assertStatus(401);
    }

    public function test_me_loans_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/loans', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['loans'],
        ]);
    }

    public function test_me_card_requires_auth(): void
    {
        $this->getJson('/api/v1/me/card')->assertStatus(401);
    }

    public function test_me_card_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/card', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['card'],
        ]);
    }

    public function test_me_profile_requires_auth(): void
    {
        $this->getJson('/api/v1/me/profile')->assertStatus(401);
    }

    public function test_me_profile_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/profile', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['name', 'email', 'phone', 'gender'],
        ]);
    }

    public function test_me_profile_change_requests_page_data_requires_auth(): void
    {
        $this->getJson('/api/v1/me/profile-change-requests/page-data')->assertStatus(401);
    }

    public function test_me_profile_change_requests_page_data_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/profile-change-requests/page-data', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['user', 'faculties', 'departments', 'cohorts', 'myRequests'],
        ]);
    }

    public function test_me_profile_update_returns_success(): void
    {
        [$user, $token] = $this->createUserAndToken();

        $response = $this->putJson('/api/v1/me/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '0999999999',
            'gender' => 'male',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonPath('data.name', 'Updated Name');
    }
}
