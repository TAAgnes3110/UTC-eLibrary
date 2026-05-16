<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SepayWebhookDigitalOrderTest extends TestCase
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
            'code' => 'C-SEPAY-WH-01',
            'name' => 'Phân loại test webhook SePay',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-SEPAY-WH-01',
            'name' => 'Kho test webhook SePay',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Luận văn test webhook SePay',
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
            'path' => 'digital/test-sepay-webhook.pdf',
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
    public function sepay_webhook_marks_order_paid_when_merchant_reference_only_in_transfer_content(): void
    {
        [$user, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $create->assertStatus(201);
        $ref = (string) $create->json('data.order.merchant_reference');
        $amount = (int) $create->json('data.order.amount_vnd');
        $publicId = (string) $create->json('data.order.public_id');

        $payload = [
            'id' => 900_001,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => null,
            'content' => 'Chuyen tien '.$ref.' cam on',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'FT900001',
        ];

        $wh = $this->postJson('/api/v1/sepay/webhook', $payload);
        $wh->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSame('paid', (string) DB::table('orders')->where('public_id', $publicId)->value('status'));
        $this->assertSame(1, (int) DB::table('payment_transactions')->where('idempotency_key', '900001')->count());
        $this->assertSame(1, (int) DB::table('digital_asset_pdf_download_entitlements')
            ->where('user_id', $user->id)
            ->where('digital_asset_id', $assetId)
            ->whereNull('revoked_at')
            ->count());
    }

    #[Test]
    public function sepay_webhook_is_idempotent_by_sepay_transaction_id(): void
    {
        [, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $create->assertStatus(201);
        $ref = (string) $create->json('data.order.merchant_reference');
        $amount = (int) $create->json('data.order.amount_vnd');
        $publicId = (string) $create->json('data.order.public_id');

        $payload = [
            'id' => 900_002,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => $ref,
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'FT900002',
        ];

        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);
        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);

        $this->assertSame('paid', (string) DB::table('orders')->where('public_id', $publicId)->value('status'));
        $this->assertSame(1, (int) DB::table('payment_transactions')->where('idempotency_key', '900002')->count());
    }

    #[Test]
    public function sepay_webhook_does_not_mark_paid_when_merchant_reference_cannot_be_resolved(): void
    {
        [, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $create->assertStatus(201);
        $amount = (int) $create->json('data.order.amount_vnd');
        $publicId = (string) $create->json('data.order.public_id');

        $payload = [
            'id' => 900_003,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => null,
            'content' => 'Khong co ma tham chieu hop le',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'FT900003',
        ];

        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);

        $this->assertSame('pending', (string) DB::table('orders')->where('public_id', $publicId)->value('status'));
        $this->assertSame(0, (int) DB::table('payment_transactions')->where('idempotency_key', '900003')->count());
    }

    #[Test]
    public function sepay_webhook_does_not_grant_entitlement_when_transfer_amount_is_insufficient(): void
    {
        [$user, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $create->assertStatus(201);
        $ref = (string) $create->json('data.order.merchant_reference');
        $amount = (int) $create->json('data.order.amount_vnd');
        $publicId = (string) $create->json('data.order.public_id');

        $payload = [
            'id' => 900_004,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => $ref,
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => max(0, $amount - 1000),
            'referenceCode' => 'FT900004',
        ];

        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);

        $this->assertSame('failed', (string) DB::table('orders')->where('public_id', $publicId)->value('status'));
        $this->assertSame('failed', (string) DB::table('payment_transactions')->where('idempotency_key', '900004')->value('status'));
        $this->assertSame(0, (int) DB::table('digital_asset_pdf_download_entitlements')
            ->where('user_id', $user->id)
            ->where('digital_asset_id', $assetId)
            ->whereNull('revoked_at')
            ->count());
    }

    #[Test]
    public function sepay_webhook_still_marks_paid_when_order_was_cancelled_before_webhook(): void
    {
        [$user, $token] = $this->createUserAndToken();
        [, $assetId] = $this->seedBookWithDigitalAsset();

        $create = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $create->assertStatus(201);
        $ref = (string) $create->json('data.order.merchant_reference');
        $amount = (int) $create->json('data.order.amount_vnd');
        $publicId = (string) $create->json('data.order.public_id');

        DB::table('orders')->where('public_id', $publicId)->update(['status' => 'cancelled']);

        $payload = [
            'id' => 900_005,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => $ref,
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'FT900005',
        ];

        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);

        $this->assertSame('paid', (string) DB::table('orders')->where('public_id', $publicId)->value('status'));
        $this->assertSame(1, (int) DB::table('digital_asset_pdf_download_entitlements')
            ->where('user_id', $user->id)
            ->where('digital_asset_id', $assetId)
            ->whereNull('revoked_at')
            ->count());
    }
}
