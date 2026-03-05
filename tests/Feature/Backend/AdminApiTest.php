<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test các route API admin (users, books, faculties, categories, roles, profile-change-requests)
 * với token có quyền SUPER_ADMIN.
 *
 * @see routes/api.php
 */
class AdminApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * GET /api/v1/faculties với admin token trả 200.
     *
     * @return void
     */
    public function test_faculties_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/faculties', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/faculties với member token trả 403.
     *
     * @return void
     */
    public function test_faculties_index_returns_403_with_member_token(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/faculties', $this->apiTokenHeaders($token));

        $response->assertStatus(403);
    }

    /**
     * GET /api/v1/users với admin token trả 200.
     *
     * @return void
     */
    public function test_users_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/users', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/categories với admin token trả 200.
     *
     * @return void
     */
    public function test_categories_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/categories', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/books với admin token trả 200.
     *
     * @return void
     */
    public function test_books_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/books', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/roles với admin token trả 200.
     *
     * @return void
     */
    public function test_roles_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/roles', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/profile-change-requests với admin token trả 200.
     *
     * @return void
     */
    public function test_profile_change_requests_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/profile-change-requests', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => ['data', 'meta', 'faculties', 'departments', 'statusFilter'],
        ]);
    }
}
