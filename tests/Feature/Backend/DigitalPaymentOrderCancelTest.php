<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalPaymentOrderCancelTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function seedBookWithDigitalAsset(): array
    {
        $now = now();

        if (! DB::table('library_settings')->where('key', LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND)->exists()) {
            DB::table('library_settings')->insert([
                'key' => LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND,
                'type' => 'int',
                'value' => '10000',
                'json_value' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
            ]);
        }

        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'C-DPAY-CXL-01',
            'name' => 'Phân loại test hủy đơn',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-DPAY-CXL-01',
            'name' => 'Kho test hủy đơn',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Luận văn test hủy đơn',
            'warehouse_id' => $warehouseId,
            'classification_id' => $classificationId,
            'quantity' => 0,
            'resource_type' => 'digital',
            'access_mode' => 'circulation_only',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        $assetId = DB::table('digital_assets')->insertGetId([
            'book_id' => $bookId,
            'version' => 1,
            'is_primary' => 1,
            'storage_disk' => 'public',
            'path' => 'digital/test-cancel.pdf',
            'original_name' => 'test.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        return [$bookId, $assetId, $classificationId];
    }

    #[Test]
    public function reader_can_cancel_pending_digital_order(): void
    {
        [$user, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $create->assertStatus(201);
        $publicId = (string) $create->json('data.order.public_id');
        $this->assertNotSame('', $publicId);

        $cancel = $this->postJson(
            "/api/v1/me/orders/{$publicId}/cancel",
            [],
            $this->apiTokenHeaders($token)
        );
        $cancel->assertStatus(200)->assertJsonPath('status', 'success');

        $this->assertSame('cancelled', (string) DB::table('orders')->where('public_id', $publicId)->value('status'));

        $again = $this->postJson(
            "/api/v1/me/orders/{$publicId}/cancel",
            [],
            $this->apiTokenHeaders($token)
        );
        $again->assertStatus(422);
    }

    #[Test]
    public function cancel_returns_404_for_other_users_order(): void
    {
        [$userA, $tokenA] = $this->createUserAndToken(['email' => 'a@test.com']);
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($tokenA)
        );
        $create->assertStatus(201);
        $publicId = (string) $create->json('data.order.public_id');

        [$userB, $tokenB] = $this->createUserAndToken(['email' => 'b@test.com']);

        $cancel = $this->postJson(
            "/api/v1/me/orders/{$publicId}/cancel",
            [],
            $this->apiTokenHeaders($tokenB)
        );
        $cancel->assertStatus(404);
    }
}
