<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test route API: health (public), refresh 401, các route bảo vệ trả 401 khi không có token.
 *
 * @see routes/api.php
 */
class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GET /api/health trả 200 với status, checks, timestamp.
     *
     * @return void
     */
    public function test_health_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'checks', 'timestamp'])
            ->assertJsonPath('checks.database', true);
    }

    /**
     * POST /api/v1/auth/refresh không có token trả 401.
     *
     * @return void
     */
    public function test_refresh_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401)->assertJsonPath('status', 'error');
    }

    /**
     * Các route bảo vệ trả 401 khi không có token.
     *
     * @param  string  $method  GET, POST, PUT, DELETE
     * @param  string  $uri  URL route
     * @param  array<string, mixed>  $data  Dữ liệu body (POST/PUT)
     * @return void
     */
    #[DataProvider('protectedRoutesProvider')]
    public function test_protected_routes_return_401_without_auth(string $method, string $uri, array $data = []): void
    {
        if ($method === 'GET') {
            $response = $this->getJson($uri);
        } elseif ($method === 'POST') {
            $response = $this->postJson($uri, $data);
        } elseif ($method === 'PUT') {
            $response = $this->putJson($uri, $data);
        } else {
            $response = $this->deleteJson($uri);
        }

        $response->assertStatus(401);
    }

    /**
     * Danh sách route cần auth.
     *
     * @return array<string, array{0: string, 1: string, 2?: array<string, mixed>}>
     */
    public static function protectedRoutesProvider(): array
    {
        $base = '/api/v1';
        return [
            'auth user' => ['GET', "{$base}/auth/user"],
            'master-data' => ['GET', "{$base}/master-data"],
            'me profile' => ['GET', "{$base}/me/profile"],
            'me profile put' => ['PUT', "{$base}/me/profile", ['name' => 'x', 'email' => 'x@x.com']],
            'me dashboard' => ['GET', "{$base}/me/dashboard"],
            'me loans' => ['GET', "{$base}/me/loans"],
            'me card' => ['GET', "{$base}/me/card"],
            'me profile-change-requests page-data' => ['GET', "{$base}/me/profile-change-requests/page-data"],
            'users index' => ['GET', "{$base}/users"],
            'users trash' => ['GET', "{$base}/users/trash"],
            'books index' => ['GET', "{$base}/books"],
            'books trash' => ['GET', "{$base}/books/trash"],
            'roles index' => ['GET', "{$base}/roles"],
            'permissions index' => ['GET', "{$base}/permissions"],
            'faculties index' => ['GET', "{$base}/faculties"],
            'categories index' => ['GET', "{$base}/categories"],
            'profile-change-requests index' => ['GET', "{$base}/profile-change-requests"],
        ];
    }
}
