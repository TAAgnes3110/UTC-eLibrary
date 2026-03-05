<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test API master-data: GET /api/v1/master-data.
 *
 * @see App\Http\Controllers\Api\MasterDataController
 */
class MasterDataApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * GET /api/v1/master-data không có token trả 401.
     *
     * @return void
     */
    public function test_master_data_requires_auth(): void
    {
        $this->getJson('/api/v1/master-data')->assertStatus(401);
    }

    /**
     * GET /api/v1/master-data với token trả faculties, departments, cohorts, role_types.
     *
     * @return void
     */
    public function test_master_data_returns_structure(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/master-data', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'data' => [
                'faculties',
                'departments',
                'cohorts',
                'role_types',
            ],
        ]);
        $this->assertIsArray($response->json('data.role_types'));
    }
}
