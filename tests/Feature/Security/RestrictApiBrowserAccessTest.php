<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestrictApiBrowserAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.api.hide_browser_access' => true]);
    }

    #[Test]
    public function browser_navigation_to_api_returns_404_json(): void
    {
        $response = $this->call(
            'GET',
            '/api/v1/news-posts/public',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
            ]
        );

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not Found']);
    }

    #[Test]
    public function spa_json_requests_still_work(): void
    {
        $this->getJson('/api/v1/news-posts/public', [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();
    }

    #[Test]
    public function health_endpoint_stays_accessible_for_monitoring(): void
    {
        config(['security.api.minimal_health' => true]);

        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonStructure(['status']);
        $response->assertJsonMissing(['checks']);
    }
}
