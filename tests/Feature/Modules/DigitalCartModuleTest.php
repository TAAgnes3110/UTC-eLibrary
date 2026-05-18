<?php

namespace Tests\Feature\Modules;

use App\Models\LibrarySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Giỏ mua tài liệu số (10 case).
 */
class DigitalCartModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

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
        $cid = DB::table('classifications')->insertGetId(['code' => 'C-'.uniqid(), 'name' => 'T', 'created_at' => $now, 'updated_at' => $now]);
        $wid = DB::table('warehouses')->insertGetId(['code' => 'W-'.uniqid(), 'name' => 'T', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $bid = DB::table('books')->insertGetId([
            'title' => 'B', 'warehouse_id' => $wid, 'classification_id' => $cid,
            'quantity' => 0, 'resource_type' => 'digital', 'access_mode' => 'circulation_only',
            'created_at' => $now, 'updated_at' => $now, 'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);

        return (int) DB::table('digital_assets')->insertGetId([
            'book_id' => $bid, 'version' => 1, 'is_primary' => 1,
            'storage_disk' => 'public', 'path' => 'digital/'.uniqid().'.pdf',
            'original_name' => 't.pdf', 'mime' => 'application/pdf', 'byte_size' => 1,
            'visibility' => 'internal', 'created_at' => $now, 'updated_at' => $now,
            'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);
    }

    #[Test]
    public function case01_unauthenticated_cart_returns_401(): void
    {
        $this->getJson('/api/v1/me/digital-purchase-cart')->assertStatus(401);
    }

    #[Test]
    public function case02_empty_cart_count_is_zero(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/digital-purchase-cart/count', $h)
            ->assertJsonPath('data.count', 0);
    }

    #[Test]
    public function case03_add_valid_asset_returns_201(): void
    {
        [, $h] = $this->studentContext();
        $id = $this->seedAsset();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $h)
            ->assertStatus(201);
    }

    #[Test]
    public function case04_add_nonexistent_asset_returns_404(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => 9999999], $h)
            ->assertStatus(404);
    }

    #[Test]
    public function case05_add_zero_id_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => 0], $h)
            ->assertStatus(422);
    }

    #[Test]
    public function case06_carts_isolated_between_users(): void
    {
        $id = $this->seedAsset();
        [, $hA] = $this->studentContext();
        $userB = User::factory()->create(['email' => 'cart-b@t.com']);
        $tokenB = JWTAuth::fromUser($userB);
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $hA);
        $this->getJson('/api/v1/me/digital-purchase-cart/count', $this->bearer($tokenB))
            ->assertJsonPath('data.count', 0);
    }

    #[Test]
    public function case07_remove_item_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $id = $this->seedAsset();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $h);
        $this->deleteJson("/api/v1/me/digital-purchase-cart/items/{$id}", [], $h)->assertStatus(200);
    }

    #[Test]
    public function case08_bulk_delete_empty_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/digital-purchase-cart/items/bulk-delete', [
            'digital_asset_ids' => [],
        ], $h)->assertStatus(422);
    }

    #[Test]
    public function case09_list_has_items_structure(): void
    {
        [, $h] = $this->studentContext();
        $id = $this->seedAsset();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', ['digital_asset_id' => $id], $h);
        $this->getJson('/api/v1/me/digital-purchase-cart', $h)
            ->assertJsonStructure(['data' => ['items']]);
    }

    #[Test]
    public function case10_add_missing_asset_id_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', [], $h)->assertStatus(422);
    }
}
