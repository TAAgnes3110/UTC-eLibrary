<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeDigitalOrderListTest extends TestCase
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
            'code' => 'C-DORD-LST-01',
            'name' => 'Phân loại test đơn hàng',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-DORD-LST-01',
            'name' => 'Kho test đơn hàng',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Luận văn test đơn hàng',
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
            'path' => 'digital/test-orders.pdf',
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
    public function reader_can_list_and_summarize_digital_orders(): void
    {
        [$user, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->withToken($token)->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
        );
        $create->assertCreated();
        $publicId = (string) data_get($create->json(), 'data.order.public_id');
        $this->assertNotSame('', $publicId);

        $summary = $this->withToken($token)->getJson('/api/v1/me/digital-orders/summary');
        $summary->assertOk();
        $summary->assertJsonPath('data.total_orders', 1);
        $summary->assertJsonPath('data.pending_count', 1);
        $summary->assertJsonPath('data.paid_count', 0);

        $list = $this->withToken($token)->getJson('/api/v1/me/digital-orders?status=pending');
        $list->assertOk();
        $list->assertJsonPath('data.data.0.public_id', $publicId);
        $list->assertJsonPath('data.data.0.status', 'pending');
        $list->assertJsonPath('data.data.0.can_pay', true);
        $this->assertStringContainsString('Luận văn', (string) $list->json('data.data.0.product_summary'));

        $this->withToken($token)->getJson('/api/v1/me/digital-orders?search='.$publicId)
            ->assertOk()
            ->assertJsonPath('data.data.0.public_id', $publicId);

        [, $otherToken] = $this->createUserAndToken(['email' => 'other-reader-orders@test.com']);
        $otherList = $this->withToken($otherToken)->getJson('/api/v1/me/digital-orders');
        $otherList->assertOk();
        $this->assertSame([], $otherList->json('data.data'));
    }
}
