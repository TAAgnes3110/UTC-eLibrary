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
     * @param  string  $method  GET, POST, PUT, PATCH, DELETE
     * @param  string  $uri  URL route
     * @param  array<string, mixed>  $data  Dữ liệu body (POST/PUT)
     */
    #[DataProvider('protectedRoutesProvider')]
    public function test_protected_routes_return_401_without_auth(
        string $method,
        string $uri,
        array $data = [],
        array $allowedStatuses = [401]
    ): void {
        if ($method === 'GET') {
            $response = $this->getJson($uri);
        } elseif ($method === 'POST') {
            $response = $this->postJson($uri, $data);
        } elseif ($method === 'PUT') {
            $response = $this->putJson($uri, $data);
        } elseif ($method === 'PATCH') {
            $response = $this->patchJson($uri, $data);
        } else {
            $response = $this->deleteJson($uri);
        }

        $this->assertContains($response->status(), $allowedStatuses);
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
            'me password put' => ['PUT', "{$base}/me/password", ['current_password' => 'x', 'password' => 'x12345678', 'password_confirmation' => 'x12345678']],
            'me library-card post' => ['POST', "{$base}/me/library-card", []],
            'users index' => ['GET', "{$base}/users"],
            'users export' => ['GET', "{$base}/users/export"],
            'users trash' => ['GET', "{$base}/users/trash"],
            'users avatar-bulk' => ['POST', "{$base}/users/avatar-bulk"],
            'users store' => ['POST', "{$base}/users"],
            'users toggle-status' => ['POST', "{$base}/users/1/toggle-status"],
            'users avatar update' => ['POST', "{$base}/users/1/avatar"],
            'users show' => ['GET', "{$base}/users/1", [], [401, 404]],
            'users update' => ['PUT', "{$base}/users/1", ['name' => 'x']],
            'users delete' => ['DELETE', "{$base}/users/1"],
            'users restore many' => ['POST', "{$base}/users/restore"],
            'users restore one' => ['POST', "{$base}/users/restore/1"],
            'users force many post' => ['POST', "{$base}/users/force"],
            'users force many delete' => ['DELETE', "{$base}/users/force"],
            'users force one' => ['DELETE', "{$base}/users/force/1"],
            'books index' => ['GET', "{$base}/books"],
            'books trash' => ['GET', "{$base}/books/trash"],
            'books import template' => ['GET', "{$base}/books/import-template"],
            'books export' => ['GET', "{$base}/books/export"],
            'books import' => ['POST', "{$base}/books/import"],
            'books store' => ['POST', "{$base}/books"],
            'books digital asset store' => ['POST', "{$base}/books/1/digital-assets", [], [401, 404]],
            'books digital asset delete' => ['DELETE', "{$base}/books/1/digital-assets/1", [], [401, 404]],
            'books show' => ['GET', "{$base}/books/1", [], [401, 404]],
            'books update' => ['PUT', "{$base}/books/1", ['title' => 'x'], [401, 404]],
            'books delete' => ['DELETE', "{$base}/books/1", [], [401, 404]],
            'books restore many' => ['POST', "{$base}/books/restore"],
            'books restore one' => ['POST', "{$base}/books/restore/1"],
            'books force many post' => ['POST', "{$base}/books/force"],
            'books force many delete' => ['DELETE', "{$base}/books/force", [], [401, 404, 405]],
            'books force one' => ['DELETE', "{$base}/books/force/1"],
            'books image update' => ['POST', "{$base}/books/1/image"],
            'books image bulk' => ['POST', "{$base}/books/image-bulk"],
            'roles index' => ['GET', "{$base}/roles"],
            'roles store' => ['POST', "{$base}/roles"],
            'roles show' => ['GET', "{$base}/roles/1"],
            'roles update' => ['PUT', "{$base}/roles/1", ['name' => 'x']],
            'roles delete' => ['DELETE', "{$base}/roles/1"],
            'roles add permission' => ['POST', "{$base}/roles/1/permissions"],
            'roles remove permission' => ['DELETE', "{$base}/roles/1/permissions"],
            'permissions index' => ['GET', "{$base}/permissions"],
            'permissions store' => ['POST', "{$base}/permissions"],
            'faculties index' => ['GET', "{$base}/faculties"],
            'classifications index' => ['GET', "{$base}/classifications"],
            'classifications list' => ['GET', "{$base}/classifications/list"],
            'classifications details' => ['GET', "{$base}/classifications/1/details", [], [401, 404]],
            'classifications import template' => ['GET', "{$base}/classifications/import-template"],
            'classifications store' => ['POST', "{$base}/classifications"],
            'classifications show' => ['GET', "{$base}/classifications/1", [], [401, 404]],
            'classifications update' => ['PUT', "{$base}/classifications/1", ['name' => 'x'], [401, 404]],
            'classifications delete' => ['DELETE', "{$base}/classifications/1", [], [401, 404]],
            'classification-details index' => ['GET', "{$base}/classification-details"],
            'classification-details import template' => ['GET', "{$base}/classification-details/import-template"],
            'classification-details store' => ['POST', "{$base}/classification-details"],
            'classification-details show' => ['GET', "{$base}/classification-details/1", [], [401, 404]],
            'classification-details update' => ['PUT', "{$base}/classification-details/1", ['name' => 'x'], [401, 404]],
            'classification-details delete' => ['DELETE', "{$base}/classification-details/1", [], [401, 404]],
            'warehouses index' => ['GET', "{$base}/warehouses"],
            'warehouses export' => ['GET', "{$base}/warehouses/export"],
            'warehouses import template' => ['GET', "{$base}/warehouses/import-template"],
            'warehouses import' => ['POST', "{$base}/warehouses/import"],
            'warehouses trash' => ['GET', "{$base}/warehouses/trash"],
            'warehouses restore many' => ['POST', "{$base}/warehouses/restore"],
            'warehouses restore one' => ['POST', "{$base}/warehouses/restore/1"],
            'warehouses force many post' => ['POST', "{$base}/warehouses/force"],
            'warehouses force many delete' => ['DELETE', "{$base}/warehouses/force"],
            'warehouses force one' => ['DELETE', "{$base}/warehouses/force/1"],
            'warehouses toggle-status' => ['POST', "{$base}/warehouses/1/toggle-status"],
            'warehouses store' => ['POST', "{$base}/warehouses"],
            'warehouses show' => ['GET', "{$base}/warehouses/1", [], [401, 404]],
            'warehouses update' => ['PUT', "{$base}/warehouses/1", ['name' => 'x']],
            'warehouses delete' => ['DELETE', "{$base}/warehouses/1", [], [401, 404]],
            'authors index' => ['GET', "{$base}/authors"],
            'authors store' => ['POST', "{$base}/authors"],
            'authors update' => ['PUT', "{$base}/authors/1", ['name' => 'x'], [401, 404]],
            'authors delete' => ['DELETE', "{$base}/authors/1", [], [401, 404]],
            'publishers index' => ['GET', "{$base}/publishers"],
            'publishers store' => ['POST', "{$base}/publishers"],
            'publishers update' => ['PUT', "{$base}/publishers/1", ['name' => 'x'], [401, 404]],
            'publishers delete' => ['DELETE', "{$base}/publishers/1", [], [401, 404]],
            'library-cards index' => ['GET', "{$base}/library-cards"],
            'library-cards export' => ['GET', "{$base}/library-cards/export"],
            'library-cards trash' => ['GET', "{$base}/library-cards/trash"],
            'library-cards restore many' => ['POST', "{$base}/library-cards/restore", ['ids' => [1]]],
            'library-cards restore one' => ['POST', "{$base}/library-cards/restore/1"],
            'library-cards force many post' => ['POST', "{$base}/library-cards/force", ['ids' => [1]]],
            'library-cards force many delete' => ['DELETE', "{$base}/library-cards/force", [], [401, 404, 405]],
            'library-cards force one' => ['DELETE', "{$base}/library-cards/force/1"],
            'library-cards store admin' => ['POST', "{$base}/library-cards", []],
            'library-cards show' => ['GET', "{$base}/library-cards/1", [], [401, 404]],
            'library-cards update' => ['PUT', "{$base}/library-cards/1", ['full_name' => 'x'], [401, 404]],
            'library-cards delete' => ['DELETE', "{$base}/library-cards/1", [], [401, 404]],
            'library-cards approve-review' => ['POST', "{$base}/library-cards/1/approve-review", [], [401, 404]],
            'library-cards reject-review' => ['POST', "{$base}/library-cards/1/reject-review", [], [401, 404]],
            'library-cards photo' => ['POST', "{$base}/library-cards/1/photo", [], [401, 404]],
        ];
    }
}
