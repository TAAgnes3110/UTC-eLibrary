<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListEndpointsRobustnessTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_users_list_handles_keyword_and_pagination(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/users?keyword=test&per_page=20&page=1', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_books_list_handles_sort_and_search_in(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/books?keyword=lap%20trinh&sort=name_asc&search_in=title,authors&per_page=20', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_loans_list_handles_filters_without_server_error(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/loans?status=da_muon&sort=due_asc&per_page=20&page=1', $this->apiTokenHeaders($token));

        $this->assertNotSame(500, $response->status());
        $response->assertJsonStructure(['status']);
    }

    public function test_library_cards_list_handles_filters_without_server_error(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/library-cards?keyword=sv&holder_type=student&status=1&sort_by=newest&per_page=20', $this->apiTokenHeaders($token));

        $this->assertNotSame(500, $response->status());
        $response->assertJsonStructure(['status']);
    }
}

