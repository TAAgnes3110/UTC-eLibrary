<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test các route API hiện có: health (public), resource routes (yêu cầu auth).
 */
class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'checks', 'timestamp'])
            ->assertJsonPath('checks.database', true);
    }

    public function test_refresh_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    #[DataProvider('protectedRoutesProvider')]
    public function test_protected_routes_return_401_without_auth(string $method, string $uri): void
    {
        $response = $method === 'GET'
            ? $this->getJson($uri)
            : $this->postJson($uri, []);

        $response->assertStatus(401);
    }

    public static function protectedRoutesProvider(): array
    {
        $base = '/api/v1';
        return [
            'users index' => ['GET', "{$base}/users"],
            'users trash' => ['GET', "{$base}/users/trash"],
            'authors index' => ['GET', "{$base}/authors"],
            'authors trash' => ['GET', "{$base}/authors/trash"],
            'books index' => ['GET', "{$base}/books"],
            'books trash' => ['GET', "{$base}/books/trash"],
            'roles index' => ['GET', "{$base}/roles"],
            'permissions index' => ['GET', "{$base}/permissions"],
            'auth user' => ['GET', "{$base}/auth/user"],
        ];
    }
}
