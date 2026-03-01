<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_master_data_requires_auth(): void
    {
        $this->getJson('/api/v1/master-data')->assertStatus(401);
    }

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
