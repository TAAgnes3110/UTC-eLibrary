<?php

namespace Tests\Feature\Backend;

use App\Enums\RoleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test các route API admin (users, faculties, ...) với token có quyền.
 */
class AdminApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_faculties_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/faculties', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    public function test_faculties_index_returns_403_with_member_token(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/faculties', $this->apiTokenHeaders($token));

        $response->assertStatus(403);
    }

    public function test_users_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/users', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    public function test_categories_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/categories', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    public function test_books_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/books', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

    public function test_roles_index_returns_200_with_admin_token(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/roles', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }

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
