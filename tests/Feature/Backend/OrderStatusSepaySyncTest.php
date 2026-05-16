<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderStatusSepaySyncTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: int}
     */
    private function seedBookWithDigitalAsset(): array
    {
        $now = now();

        if (! DB::table('library_settings')->where('key', LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND)->exists()) {
            DB::table('library_settings')->insert([
                'key' => LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND,
                'type' => 'int',
                'value' => '3000',
                'json_value' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
            ]);
        }

        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'C-ORD-SYNC-01',
            'name' => 'Phân loại test sync SePay',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-ORD-SYNC-01',
            'name' => 'Kho test sync SePay',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Luận văn test sync SePay',
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
            'path' => 'digital/test-sync.pdf',
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

        return [$bookId, $assetId];
    }

    #[Test]
    public function order_status_sync_marks_paid_when_sepay_api_returns_matching_transaction(): void
    {
        Config::set('services.sepay.api_token', 'test-token');
        Config::set('services.sepay.api_base_url', 'https://userapi.sepay.vn/v2');

        [, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $create->assertStatus(201);
        $ref = (string) $create->json('data.order.merchant_reference');
        $publicId = (string) $create->json('data.order.public_id');
        $amount = (int) $create->json('data.order.amount_vnd');

        Http::fake([
            'userapi.sepay.vn/v2/transactions*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                        'transaction_date' => now()->format('Y-m-d H:i:s'),
                        'account_number' => '1234567890',
                        'transfer_type' => 'in',
                        'amount_in' => $amount,
                        'transaction_content' => 'CK '.$ref,
                        'reference_number' => 'FT-SYNC-01',
                        'code' => $ref,
                        'bank_brand_name' => 'MBBank',
                    ],
                ],
                'meta' => ['pagination' => ['total' => 1, 'per_page' => 50, 'current_page' => 1, 'last_page' => 1, 'has_more' => false]],
            ], 200),
        ]);

        $sync = $this->getJson(
            "/api/v1/me/orders/{$publicId}?sync=1",
            $this->apiTokenHeaders($token)
        );

        $sync->assertStatus(200)
            ->assertJsonPath('data.order.status', 'paid')
            ->assertJsonPath('data.synced_from_sepay', true)
            ->assertJsonPath('data.sepay_sync_available', true);

        $this->assertSame('paid', (string) DB::table('orders')->where('public_id', $publicId)->value('status'));
    }
}
