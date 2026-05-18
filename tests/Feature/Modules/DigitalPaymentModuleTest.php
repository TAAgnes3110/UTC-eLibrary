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
 * Module: Thanh toán tài liệu số (10 case).
 */
class DigitalPaymentModuleTest extends TestCase
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
    public function case01_unauthenticated_create_order_returns_401(): void
    {
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [1],
        ])->assertStatus(401);
    }

    #[Test]
    public function case02_create_order_empty_body_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/digital-payment-orders', [], $h)->assertStatus(422);
    }

    #[Test]
    public function case03_digital_purchase_does_not_require_library_card(): void
    {
        [$user, $h] = $this->studentContext();
        $this->assertDatabaseMissing('library_cards', ['user_id' => $user->id]);

        $id = $this->seedAsset();
        $this->postJson('/api/v1/me/digital-purchase-cart/items', [
            'digital_asset_id' => $id,
        ], $h)->assertStatus(201);

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$id],
        ], $h)->assertStatus(201)->assertJsonPath('data.order.status', 'pending');
    }

    #[Test]
    public function case03b_create_order_valid_returns_201_pending(): void
    {
        [, $h] = $this->studentContext();
        $id = $this->seedAsset();
        $r = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$id],
        ], $h)->assertStatus(201);
        $this->assertSame('pending', $r->json('data.order.status'));
    }

    #[Test]
    public function case04_user_cannot_view_other_users_order(): void
    {
        $userA = User::factory()->create(['email' => 'pa@t.com']);
        $userB = User::factory()->create(['email' => 'pb@t.com']);
        $tokenA = JWTAuth::fromUser($userA);
        $tokenB = JWTAuth::fromUser($userB);
        $id = $this->seedAsset();
        $create = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$id],
        ], $this->bearer($tokenA))->assertStatus(201);
        $publicId = $create->json('data.order.public_id');
        $status = $this->getJson("/api/v1/me/orders/{$publicId}", $this->bearer($tokenB))->status();
        $this->assertContains($status, [404, 410], 'Mong đợi: không xem được đơn người khác.');
    }

    #[Test]
    public function case05_duplicate_asset_ids_in_order_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $id = $this->seedAsset();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$id, $id],
        ], $h)->assertStatus(422);
    }

    #[Test]
    public function case06_digital_orders_list_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/digital-orders', $h)->assertSuccessful();
    }

    #[Test]
    public function case07_digital_orders_summary_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/digital-orders/summary', $h)->assertSuccessful();
    }

    #[Test]
    public function case08_cancel_nonexistent_order_returns_404(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/orders/00000000-0000-0000-0000-000000000099/cancel', [], $h)
            ->assertStatus(404);
    }

    #[Test]
    public function case09_more_than_50_assets_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => range(1, 51),
        ], $h)->assertStatus(422);
    }

    #[Test]
    public function case10_order_amount_matches_library_price(): void
    {
        [, $h] = $this->studentContext();
        $id = $this->seedAsset();
        $r = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$id],
        ], $h)->assertStatus(201);
        $this->assertSame(10000, $r->json('data.order.amount_vnd'));
    }
}
