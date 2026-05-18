<?php

namespace Tests\Feature\Payment;

use App\Models\LibrarySetting;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Backend\ActsAsApiUser;
use Tests\TestCase;

/**
 * Kịch bản thanh toán số “kinh điển”: giá server-side, multi-item, over/underpay, webhook, expired/cancel.
 */
class DigitalPaymentClassicScenariosTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    private function seedLibraryDefaultPrice(int $priceVnd = 10000): void
    {
        $key = LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND;
        if (! DB::table('library_settings')->where('key', $key)->exists()) {
            DB::table('library_settings')->insert([
                'key' => $key,
                'type' => 'int',
                'value' => (string) $priceVnd,
                'json_value' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
            ]);
        }
    }

    private function seedDigitalAsset(?int $priceVnd = null): int
    {
        $this->seedLibraryDefaultPrice($priceVnd ?? 10000);
        $now = now();

        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'C-'.uniqid(),
            'name' => 'Pay classic',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'W-'.uniqid(),
            'name' => 'Pay classic WH',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $bookId = DB::table('books')->insertGetId([
            'title' => 'Sách test thanh toán',
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

        $assetId = (int) DB::table('digital_assets')->insertGetId([
            'book_id' => $bookId,
            'version' => 1,
            'is_primary' => 1,
            'storage_disk' => 'public',
            'path' => 'digital/'.uniqid().'.pdf',
            'original_name' => 'classic.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        if ($priceVnd !== null) {
            DB::table('digital_asset_paywall_settings')->insert([
                'digital_asset_id' => $assetId,
                'is_paywall_enabled' => 1,
                'pdf_download_price_vnd' => $priceVnd,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $assetId;
    }

    /**
     * @param  list<int>  $assetIds
     * @return array{user_id: int, public_id: string, merchant_reference: string, amount_vnd: int, asset_ids: list<int>}
     */
    private function createPendingOrder(array $assetIds, string $token): array
    {
        $response = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => $assetIds],
            $this->apiTokenHeaders($token)
        );
        $response->assertStatus(201);

        return [
            'user_id' => (int) DB::table('orders')
                ->where('public_id', $response->json('data.order.public_id'))
                ->value('user_id'),
            'public_id' => (string) $response->json('data.order.public_id'),
            'merchant_reference' => (string) $response->json('data.order.merchant_reference'),
            'amount_vnd' => (int) $response->json('data.order.amount_vnd'),
            'asset_ids' => $assetIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function sepayPayload(string $merchantRef, int $amount, int $sepayId, array $overrides = []): array
    {
        return array_merge([
            'id' => $sepayId,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456789',
            'code' => $merchantRef,
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'FT'.$sepayId,
        ], $overrides);
    }

    private function postSepayWebhook(array $payload): void
    {
        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200)->assertJson(['success' => true]);
    }

    private function entitlementCount(int $userId, int $assetId): int
    {
        return (int) DB::table('digital_asset_pdf_download_entitlements')
            ->where('user_id', $userId)
            ->where('digital_asset_id', $assetId)
            ->whereNull('revoked_at')
            ->count();
    }

    #[Test]
    public function create_order_ignores_client_supplied_amount_fields(): void
    {
        [, $token] = $this->createUserAndToken(['email' => 'tamper-amount@example.com']);
        $assetId = $this->seedDigitalAsset(10000);

        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
            'amount_vnd' => 1,
            'total_vnd' => 1,
            'total_vnd_snapshot' => 1,
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(201);
        $this->assertSame(10000, $response->json('data.order.amount_vnd'));
        $this->assertSame(
            10000,
            (int) DB::table('orders')->where('public_id', $response->json('data.order.public_id'))->value('total_vnd_snapshot')
        );
    }

    #[Test]
    public function expired_order_webhook_with_sufficient_amount_still_marks_paid(): void
    {
        [$user, $token] = $this->createUserAndToken(['email' => 'expired-pay@example.com']);
        $assetId = $this->seedDigitalAsset();
        $order = $this->createPendingOrder([$assetId], $token);

        DB::table('orders')->where('public_id', $order['public_id'])->update([
            'status' => Order::STATUS_EXPIRED,
            'price_locked_until' => now()->subMinute(),
        ]);

        $this->postSepayWebhook($this->sepayPayload(
            $order['merchant_reference'],
            $order['amount_vnd'],
            910_001
        ));

        $this->assertSame(
            Order::STATUS_PAID,
            (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status')
        );
        $this->assertSame(1, $this->entitlementCount((int) $user->id, $assetId));
    }

    #[Test]
    public function multi_asset_order_charges_sum_of_prices_and_grants_all_entitlements(): void
    {
        [$user, $token] = $this->createUserAndToken(['email' => 'multi-ok@example.com']);
        $assetA = $this->seedDigitalAsset(7000);
        $assetB = $this->seedDigitalAsset(3000);
        $order = $this->createPendingOrder([$assetA, $assetB], $token);

        $this->assertSame(10000, $order['amount_vnd']);

        $this->postSepayWebhook($this->sepayPayload(
            $order['merchant_reference'],
            10000,
            910_002
        ));

        $this->assertSame(Order::STATUS_PAID, (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status'));
        $this->assertSame(1, $this->entitlementCount((int) $user->id, $assetA));
        $this->assertSame(1, $this->entitlementCount((int) $user->id, $assetB));
    }

    #[Test]
    public function multi_asset_order_underpayment_does_not_grant_entitlements(): void
    {
        [$user, $token] = $this->createUserAndToken(['email' => 'multi-under@example.com']);
        $assetA = $this->seedDigitalAsset(8000);
        $assetB = $this->seedDigitalAsset(7000);
        $order = $this->createPendingOrder([$assetA, $assetB], $token);

        $this->assertSame(15000, $order['amount_vnd']);

        $this->postSepayWebhook($this->sepayPayload(
            $order['merchant_reference'],
            14000,
            910_003
        ));

        $this->assertSame(Order::STATUS_FAILED, (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status'));
        $this->assertSame(0, $this->entitlementCount((int) $user->id, $assetA));
        $this->assertSame(0, $this->entitlementCount((int) $user->id, $assetB));
    }

    #[Test]
    public function overpayment_marks_paid_with_single_entitlement_per_asset(): void
    {
        [$user, $token] = $this->createUserAndToken(['email' => 'overpay@example.com']);
        $assetId = $this->seedDigitalAsset();
        $order = $this->createPendingOrder([$assetId], $token);

        $this->postSepayWebhook($this->sepayPayload(
            $order['merchant_reference'],
            $order['amount_vnd'] + 5000,
            910_004
        ));

        $this->assertSame(Order::STATUS_PAID, (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status'));
        $this->assertSame(1, $this->entitlementCount((int) $user->id, $assetId));
        $this->assertSame(1, (int) DB::table('payment_transactions')->where('idempotency_key', '910004')->count());
    }

    #[Test]
    public function webhook_explicit_code_pays_intended_order_when_content_mentions_other_reference(): void
    {
        [$userA, $tokenA] = $this->createUserAndToken(['email' => 'ref-a@example.com']);
        [$userB, $tokenB] = $this->createUserAndToken(['email' => 'ref-b@example.com']);
        $assetA = $this->seedDigitalAsset();
        $assetB = $this->seedDigitalAsset();

        $orderA = $this->createPendingOrder([$assetA], $tokenA);
        $orderB = $this->createPendingOrder([$assetB], $tokenB);

        $this->postSepayWebhook($this->sepayPayload(
            $orderB['merchant_reference'],
            $orderB['amount_vnd'],
            910_005,
            [
                'content' => 'CK '.$orderA['merchant_reference'].' va '.$orderB['merchant_reference'],
            ]
        ));

        $this->assertSame(
            Order::STATUS_PAID,
            (string) DB::table('orders')->where('public_id', $orderB['public_id'])->value('status')
        );
        $this->assertSame(
            Order::STATUS_PENDING,
            (string) DB::table('orders')->where('public_id', $orderA['public_id'])->value('status')
        );
        $this->assertSame(0, $this->entitlementCount((int) $userA->id, $assetA));
        $this->assertSame(1, $this->entitlementCount((int) $userB->id, $assetB));
    }

    #[Test]
    public function webhook_string_digit_transfer_amount_marks_order_paid(): void
    {
        [$user, $token] = $this->createUserAndToken(['email' => 'str-amount@example.com']);
        $assetId = $this->seedDigitalAsset();
        $order = $this->createPendingOrder([$assetId], $token);

        $this->postSepayWebhook($this->sepayPayload(
            $order['merchant_reference'],
            $order['amount_vnd'],
            910_006,
            ['transferAmount' => (string) $order['amount_vnd']]
        ));

        $this->assertSame(Order::STATUS_PAID, (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status'));
        $this->assertSame(1, $this->entitlementCount((int) $user->id, $assetId));
    }

    #[Test]
    public function webhook_non_integer_transfer_amount_is_ignored(): void
    {
        [, $token] = $this->createUserAndToken(['email' => 'float-amount@example.com']);
        $assetId = $this->seedDigitalAsset();
        $order = $this->createPendingOrder([$assetId], $token);

        $this->postJson('/api/v1/sepay/webhook', $this->sepayPayload(
            $order['merchant_reference'],
            $order['amount_vnd'],
            910_007,
            ['transferAmount' => $order['amount_vnd'] + 0.5]
        ))->assertStatus(200);

        $this->assertSame(
            Order::STATUS_PENDING,
            (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status')
        );
        $this->assertSame(0, (int) DB::table('payment_transactions')->where('idempotency_key', '910007')->count());
    }

    #[Test]
    public function webhook_negative_transfer_amount_does_not_mark_paid(): void
    {
        [, $token] = $this->createUserAndToken(['email' => 'neg-amount@example.com']);
        $assetId = $this->seedDigitalAsset();
        $order = $this->createPendingOrder([$assetId], $token);

        $this->postJson('/api/v1/sepay/webhook', $this->sepayPayload(
            $order['merchant_reference'],
            -1000,
            910_008
        ))->assertStatus(200);

        $status = (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status');
        $this->assertContains($status, [Order::STATUS_PENDING, Order::STATUS_FAILED]);
        $this->assertNotSame(Order::STATUS_PAID, $status);
    }

    #[Test]
    public function after_expire_pending_command_webhook_still_marks_paid_when_amount_sufficient(): void
    {
        [$user, $token] = $this->createUserAndToken(['email' => 'cmd-expire@example.com']);
        $assetId = $this->seedDigitalAsset();
        $order = $this->createPendingOrder([$assetId], $token);

        DB::table('orders')->where('public_id', $order['public_id'])->update([
            'price_locked_until' => now()->subMinute(),
        ]);

        Artisan::call('digital-orders:expire-pending');

        $this->assertSame(
            Order::STATUS_EXPIRED,
            (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status')
        );

        $this->postSepayWebhook($this->sepayPayload(
            $order['merchant_reference'],
            $order['amount_vnd'],
            910_009
        ));

        $this->assertSame(Order::STATUS_PAID, (string) DB::table('orders')->where('public_id', $order['public_id'])->value('status'));
        $this->assertSame(1, $this->entitlementCount((int) $user->id, $assetId));
    }

    #[Test]
    public function webhook_without_code_field_matching_only_one_of_two_pending_orders(): void
    {
        [$userA, $tokenA] = $this->createUserAndToken(['email' => 'dual-a@example.com']);
        [$userB, $tokenB] = $this->createUserAndToken(['email' => 'dual-b@example.com']);
        $assetA = $this->seedDigitalAsset();
        $assetB = $this->seedDigitalAsset();

        $orderA = $this->createPendingOrder([$assetA], $tokenA);
        $orderB = $this->createPendingOrder([$assetB], $tokenB);

        $this->postJson('/api/v1/sepay/webhook', $this->sepayPayload(
            $orderA['merchant_reference'],
            $orderA['amount_vnd'],
            910_010,
            [
                'code' => null,
                'content' => 'Thanh toan '.$orderA['merchant_reference'].' '.$orderB['merchant_reference'],
            ]
        ))->assertStatus(200);

        $paidCount = (int) DB::table('orders')
            ->whereIn('public_id', [$orderA['public_id'], $orderB['public_id']])
            ->where('status', Order::STATUS_PAID)
            ->count();

        $this->assertSame(1, $paidCount, 'Chỉ một đơn được ghi nhận paid khi nội dung CK chứa hai mã.');
        $entitlements = $this->entitlementCount((int) $userA->id, $assetA)
            + $this->entitlementCount((int) $userB->id, $assetB);
        $this->assertSame(1, $entitlements);
    }

    #[Test]
    public function second_create_order_for_same_asset_while_pending_returns_422(): void
    {
        [, $token] = $this->createUserAndToken(['email' => 'dup-pending@example.com']);
        $assetId = $this->seedDigitalAsset();

        $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        )->assertStatus(201);

        $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        )->assertStatus(422);
    }
}
