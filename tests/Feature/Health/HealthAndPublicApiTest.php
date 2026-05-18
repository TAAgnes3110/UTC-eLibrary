<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test API Health Check + Public endpoints security.
 * Kiểm tra endpoint công khai, rate limiting, response structure.
 */
class HealthAndPublicApiTest extends TestCase
{
    use RefreshDatabase;

    // ── Health Check ──────────────────────────────────────────────────────────

    #[Test]
    public function health_check_returns_200_or_503(): void
    {
        $response = $this->getJson('/api/health');
        $this->assertContains($response->status(), [200, 503]);
    }

    #[Test]
    public function health_check_returns_required_structure(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertJsonStructure(['status', 'checks', 'timestamp']);
    }

    #[Test]
    public function health_check_timestamp_is_iso8601(): void
    {
        $response = $this->getJson('/api/health');
        $timestamp = $response->json('timestamp');

        $this->assertNotNull($timestamp);
        // ISO 8601 format check
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $timestamp
        );
    }

    #[Test]
    public function health_check_is_accessible_without_auth(): void
    {
        $this->getJson('/api/health')->assertStatus(200);
    }

    // ── Public News Posts ─────────────────────────────────────────────────────

    #[Test]
    public function public_news_posts_endpoint_is_accessible_without_auth(): void
    {
        $this->getJson('/api/v1/news-posts/public')->assertSuccessful();
    }

    #[Test]
    public function news_post_by_invalid_slug_returns_404(): void
    {
        $this->getJson('/api/v1/news-posts/nonexistent-slug-xyz')->assertStatus(404);
    }

    #[Test]
    public function news_post_slug_with_uppercase_is_rejected(): void
    {
        // Route constraint: ^[a-z0-9]+(?:-[a-z0-9]+)*$
        $this->getJson('/api/v1/news-posts/Invalid-Slug')->assertStatus(404);
    }

    #[Test]
    public function news_post_slug_with_sql_injection_returns_404(): void
    {
        $this->getJson("/api/v1/news-posts/'; DROP TABLE news_posts; --")->assertStatus(404);
    }

    #[Test]
    public function news_post_slug_with_path_traversal_returns_404(): void
    {
        $this->getJson('/api/v1/news-posts/../../admin/secret')->assertStatus(404);
    }

    // ── Public Digital Document Submissions ───────────────────────────────────

    #[Test]
    public function public_digital_submissions_accessible_without_auth(): void
    {
        $this->getJson('/api/v1/digital-document-submissions')->assertSuccessful();
    }

    // ── Master Data ───────────────────────────────────────────────────────────

    #[Test]
    public function master_data_requires_authentication(): void
    {
        $this->getJson('/api/v1/master-data')->assertStatus(401);
    }

    // ── Non-existent Routes ───────────────────────────────────────────────────

    #[Test]
    public function accessing_nonexistent_api_route_returns_404(): void
    {
        $this->getJson('/api/v1/nonexistent-endpoint-xyz')->assertStatus(404);
    }

    #[Test]
    public function accessing_api_root_without_version_returns_404(): void
    {
        $this->getJson('/api/users')->assertStatus(404);
    }

    // ── Method Not Allowed ────────────────────────────────────────────────────

    #[Test]
    public function get_request_to_login_endpoint_returns_405(): void
    {
        $this->getJson('/api/v1/auth/login')->assertStatus(405);
    }

    #[Test]
    public function delete_request_to_login_endpoint_returns_405(): void
    {
        $this->deleteJson('/api/v1/auth/login')->assertStatus(405);
    }

    // ── CORS & Headers ────────────────────────────────────────────────────────

    #[Test]
    public function api_response_is_json(): void
    {
        $response = $this->get('/api/health');
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type') ?? '');
    }

    #[Test]
    public function authenticated_routes_do_not_serve_html(): void
    {
        $response = $this->getJson('/api/v1/auth/user');
        $contentType = $response->headers->get('Content-Type') ?? '';
        $this->assertStringNotContainsString('text/html', $contentType);
    }
}
