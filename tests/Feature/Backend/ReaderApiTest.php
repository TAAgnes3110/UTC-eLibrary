<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test các route API reader: /api/v1/me/* (dashboard, loans, card, profile, profile-change-requests).
 *
 * @see routes/api.php
 * @see App\Http\Controllers\Api\ReaderController
 * @see App\Http\Controllers\Api\ProfileController
 * @see App\Http\Controllers\Api\ProfileChangeRequestController
 */
class ReaderApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * GET /api/v1/me/dashboard không có token trả 401.
     *
     * @return void
     */
    public function test_me_dashboard_requires_auth(): void
    {
        $this->getJson('/api/v1/me/dashboard')->assertStatus(401);
    }

    /**
     * GET /api/v1/me/dashboard với token trả stats.
     *
     * @return void
     */
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

    /**
     * GET /api/v1/me/loans không có token trả 401.
     *
     * @return void
     */
    public function test_me_loans_requires_auth(): void
    {
        $this->getJson('/api/v1/me/loans')->assertStatus(401);
    }

    /**
     * GET /api/v1/me/loans với token trả loans.
     *
     * @return void
     */
    public function test_me_loans_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/loans', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['loans'],
        ]);
    }

    /**
     * GET /api/v1/me/card không có token trả 401.
     *
     * @return void
     */
    public function test_me_card_requires_auth(): void
    {
        $this->getJson('/api/v1/me/card')->assertStatus(401);
    }

    /**
     * GET /api/v1/me/card với token trả card.
     *
     * @return void
     */
    public function test_me_card_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/card', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['card'],
        ]);
    }

    /**
     * GET /api/v1/me/profile không có token trả 401.
     *
     * @return void
     */
    public function test_me_profile_requires_auth(): void
    {
        $this->getJson('/api/v1/me/profile')->assertStatus(401);
    }

    /**
     * GET /api/v1/me/profile với token trả thông tin profile.
     *
     * @return void
     */
    public function test_me_profile_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/profile', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['name', 'email', 'phone', 'gender'],
        ]);
    }

    /**
     * GET /api/v1/me/profile-change-requests/page-data không có token trả 401.
     *
     * @return void
     */
    public function test_me_profile_change_requests_page_data_requires_auth(): void
    {
        $this->getJson('/api/v1/me/profile-change-requests/page-data')->assertStatus(401);
    }

    /**
     * GET /api/v1/me/profile-change-requests/page-data với token trả structure.
     *
     * @return void
     */
    public function test_me_profile_change_requests_page_data_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/profile-change-requests/page-data', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['user', 'faculties', 'departments', 'cohorts', 'myRequests'],
        ]);
    }

    /**
     * PUT /api/v1/me/profile cập nhật profile thành công.
     *
     * @return void
     */
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
