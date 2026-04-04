<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test các route API admin (users, books, faculties, classifications, roles, warehouses)
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
     */
    public function test_faculties_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/faculties', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/faculties với member token trả 403.
     */
    public function test_faculties_index_returns_403_with_member_token(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/faculties', $this->apiTokenHeaders($token));

        $response->assertStatus(403);
    }

    /**
     * GET /api/v1/users với admin token trả 200.
     */
    public function test_users_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/users', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/classifications với admin token trả 200.
     */
    public function test_classifications_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/classifications', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/books với admin token trả 200.
     */
    public function test_books_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/books', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/roles với admin token trả 200.
     */
    public function test_roles_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/roles', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    /**
     * GET /api/v1/warehouses với admin token trả 200.
     */
    public function test_warehouses_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/warehouses', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }
}
