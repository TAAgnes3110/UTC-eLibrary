<?php

namespace Tests\Feature\Payment;

use App\Enums\RoleType;
use App\Models\LibrarySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test giỏ mua tài liệu số – DigitalPurchaseCart.
 * Bảo mật, validation, IDOR, data integrity.
 */
class DigitalCartSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $extra = []): array
    {
        $user = User::factory()->create(array_merge(['user_type' => RoleType::STUDENT], $extra));

        return [$user, JWTAuth::fromUser($user)];
    }

    private function auth(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    private function seedAsset(): int
    {
        $key = LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND;
        if (! DB::table('library_settings')->where('key', $key)->exists()) {
            DB::table('library_settings')->insert([
                'key' => $key, 'type' => 'int', 'value' => '10000',
                'json_value' => null, 'created_at' => now(), 'updated_at' => now(),
                'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
            ]);
        }
        $now = now();
        $cid = DB::table('classifications')->insertGetId([
            'code' => 'C-'.uniqid(), 'name' => 'Cart Class',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $wid = DB::table('warehouses')->insertGetId([
            'code' => 'W-'.uniqid(), 'name' => 'Cart WH', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $bid = DB::table('books')->insertGetId([
            'title' => 'Cart Book', 'warehouse_id' => $wid, 'classification_id' => $cid,
            'quantity' => 0, 'resource_type' => 'digital', 'access_mode' => 'circulation_only',
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);

        return (int) DB::table('digital_assets')->insertGetId([
            'book_id' => $bid, 'version' => 1, 'is_primary' => 1,
            'storage_disk' => 'public', 'path' => 'digital/'.uniqid().'.pdf',
            'original_name' => 'test.pdf', 'mime' => 'application/pdf',
            'byte_size' => 1024, 'visibility' => 'internal',
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_cannot_view_cart(): void
    {
        $this->getJson('/api/v1/me/digital-purchase-cart')->assertStatus(401);
    }

    #[Test]
    public function unauthenticated_cannot_add_to_cart(): void
    {
        $this->postJson('/api/v1/me/digital-purchase-cart/items', [
            'digital_asset_id' => 1,
        ])->assertStatus(401);
    }

    // ── Add to Cart ───────────────────────────────────────────────────────────

    #[Test]
    public function add_valid_asset_to_cart_returns_201(): void
    {
        [, $token] = $this->makeUser();
        $id = $this->seedAsset();

        $this->postJson('/api/v1/me/digital-purchase-cart/items', [
            'digital_asset_id' => $id,
        ], $this->auth($token))->assertStatus(201);
    }

    #[Test]
    public function add_nonexistent_asset_returns_404(): void
    {
        [, $token] = $this->makeUser();

        $this->postJson('/api/v1/me/digital-purchase-cart/items', [
            'digital_asset_id' => 9999999,
        ], $this->auth($token))->assertStatus(404);
    }

    #[Test]
    public function add_asset_with_zero_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', [
            'digital_asset_id' => 0,
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function add_asset_with_negative_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', [
            'digital_asset_id' => -5,
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function add_asset_with_string_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', [
            'digital_asset_id' => 'abc',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function add_asset_missing_digital_asset_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', [], $this->auth($token))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['digital_asset_id']);
    }

    #[Test]
    public function cart_count_returns_correct_number(): void
    {
        [, $token] = $this->makeUser();
        $id1 = $this->seedAsset();
        $id2 = $this->seedAsset();

        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id1], $this->auth($token));
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id2], $this->auth($token));

        $resp = $this->getJson('/api/v1/me/digital-purchase-cart/count', $this->auth($token))
            ->assertStatus(200);

        $this->assertEquals(2, $resp->json('data.count'));
    }

    #[Test]
    public function cart_of_different_users_are_isolated(): void
    {
        [$userA, $tokenA] = $this->makeUser(['email' => 'carta@example.com']);
        [, $tokenB] = $this->makeUser(['email' => 'cartb@example.com']);
        $id = $this->seedAsset();

        // UserA thêm vào giỏ
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $this->auth($tokenA));

        // UserB kiểm tra → giỏ phải rỗng
        $resp = $this->getJson('/api/v1/me/digital-purchase-cart/count', $this->auth($tokenB));
        $this->assertEquals(0, $resp->json('data.count'));
    }

    // ── Remove from Cart ─────────────────────────────────────────────────────

    #[Test]
    public function remove_asset_from_cart_returns_200(): void
    {
        [, $token] = $this->makeUser();
        $id = $this->seedAsset();

        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $this->auth($token));

        $this->deleteJson("/api/v1/me/digital-purchase-cart/items/$id", [], $this->auth($token))
            ->assertStatus(200);

        $resp = $this->getJson('/api/v1/me/digital-purchase-cart/count', $this->auth($token));
        $this->assertEquals(0, $resp->json('data.count'));
    }

    #[Test]
    public function remove_asset_not_in_cart_still_returns_200(): void
    {
        [, $token] = $this->makeUser();
        $id = $this->seedAsset();

        // Không thêm vào giỏ nhưng vẫn xóa → idempotent
        $this->deleteJson("/api/v1/me/digital-purchase-cart/items/$id", [], $this->auth($token))
            ->assertStatus(200);
    }

    #[Test]
    public function remove_nonexistent_asset_id_returns_404(): void
    {
        [, $token] = $this->makeUser();
        // ID không tồn tại trong bảng digital_assets
        $this->deleteJson('/api/v1/me/digital-purchase-cart/items/9999999', [], $this->auth($token))
            ->assertStatus(404);
    }

    // ── Bulk Delete ───────────────────────────────────────────────────────────

    #[Test]
    public function bulk_delete_removes_multiple_items(): void
    {
        [, $token] = $this->makeUser();
        $id1 = $this->seedAsset();
        $id2 = $this->seedAsset();

        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id1], $this->auth($token));
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id2], $this->auth($token));

        $this->postJson('/api/v1/me/digital-purchase-cart/items/bulk-delete', [
            'digital_asset_ids' => [$id1, $id2],
        ], $this->auth($token))->assertStatus(200);

        $resp = $this->getJson('/api/v1/me/digital-purchase-cart/count', $this->auth($token));
        $this->assertEquals(0, $resp->json('data.count'));
    }

    #[Test]
    public function bulk_delete_with_empty_array_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-purchase-cart/items/bulk-delete', [
            'digital_asset_ids' => [],
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function bulk_delete_with_non_array_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-purchase-cart/items/bulk-delete', [
            'digital_asset_ids' => 'not-array',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function bulk_delete_with_negative_ids_is_rejected_or_handled(): void
    {
        [, $token] = $this->makeUser();
        // IDs âm → phải không crash
        $response = $this->postJson('/api/v1/me/digital-purchase-cart/items/bulk-delete', [
            'digital_asset_ids' => [-1, -2],
        ], $this->auth($token));

        $this->assertContains($response->status(), [200, 422]);
    }

    // ── Cart List ─────────────────────────────────────────────────────────────

    #[Test]
    public function cart_list_returns_items_structure(): void
    {
        [, $token] = $this->makeUser();
        $id = $this->seedAsset();

        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $this->auth($token));

        $resp = $this->getJson('/api/v1/me/digital-purchase-cart', $this->auth($token))
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['items']]);

        $this->assertCount(1, $resp->json('data.items'));
    }

    #[Test]
    public function cart_list_does_not_expose_storage_path(): void
    {
        [, $token] = $this->makeUser();
        $id = $this->seedAsset();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $this->auth($token));

        $response = $this->getJson('/api/v1/me/digital-purchase-cart', $this->auth($token));
        $body = $response->content();

        // Path nội bộ không nên bị lộ trong danh sách giỏ
        $this->assertStringNotContainsString('/digital/', $body);
    }
}
