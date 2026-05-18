<?php

namespace App\Services;

use App\Models\DigitalAssetPdfDownloadEntitlement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DigitalPaymentOrderService
{
    private const ITEM_TYPE_DIGITAL_UNLOCK = 'digital_asset_unlock';

    public function __construct(
        private readonly DigitalPaywallService $paywall,
        private readonly DigitalPurchaseCartService $digitalCart,
        private readonly SepayQrService $sepayQr,
        private readonly SepayTransactionApiService $sepayTransactions
    ) {}

    public function createPendingOrderForDigitalAssets(int $userId, Collection $assets): Order
    {
        $totalPrice = 0;
        $orderItemsData = [];

        foreach ($assets as $asset) {
            $asset->loadMissing(['paywallSetting', 'book.digitalDocumentSubmission']);
            if ($this->paywall->userCanDownloadPdf($userId, $asset)) {
                continue;
            }
            $price = $this->paywall->resolvePdfDownloadPriceVnd($asset);

            if ($price > 0) {
                $totalPrice += $price;
                $orderItemsData[] = [
                    'digital_asset_id' => $asset->id,
                    'quantity' => 1,
                    'unit_price_vnd_snapshot' => $price,
                    'line_total_vnd_snapshot' => $price,
                    'meta' => [
                        'original_name' => $asset->original_name,
                        'book_id' => (int) $asset->book_id,
                    ],
                ];
            }
        }

        if ($totalPrice <= 0 || empty($orderItemsData)) {
            throw new \RuntimeException('Không có tài liệu nào hợp lệ để thanh toán trực tuyến hoặc tổng tiền bằng 0₫.');
        }

        $this->assertUserCanCreatePendingDigitalOrder($userId);

        $code = $this->generatePaymentCode();
        $lockUntil = now()->addMinutes((int) env('SEPAY_PRICE_LOCK_MINUTES', 15));
        $qrUrl = $this->sepayQr->buildQrImageUrl($totalPrice, $code);

        return DB::transaction(function () use ($userId, $totalPrice, $code, $lockUntil, $qrUrl, $orderItemsData): Order {
            $this->assertUserCanCreatePendingDigitalOrder($userId);

            $order = Order::create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $userId,
                'type' => Order::TYPE_DIGITAL_PURCHASE,
                'status' => Order::STATUS_PENDING,
                'subtotal_vnd_snapshot' => $totalPrice,
                'total_vnd_snapshot' => $totalPrice,
                'currency' => 'VND',
                'price_locked_until' => $lockUntil,
                'gateway' => Order::GATEWAY_SEPAY,
                'merchant_reference' => $code,
                'gateway_init_payload' => [
                    'qr_image_url' => $qrUrl,
                    'description_code' => $code,
                    'amount_vnd' => $totalPrice,
                ],
            ]);

            foreach ($orderItemsData as $itemData) {
                OrderItem::create(array_merge($itemData, [
                    'order_id' => $order->id,
                    'item_type' => self::ITEM_TYPE_DIGITAL_UNLOCK,
                ]));
            }

            return $order->loadMissing(['items']);
        });
    }

    public function pendingMaxAgeDays(): int
    {
        return max(1, (int) config('services.digital_orders.pending_max_age_days', 3));
    }

    public function pendingMaxCountPerUser(): int
    {
        return max(1, (int) config('services.digital_orders.pending_max_per_user', 3));
    }

    /**
     * Xóa đơn pending chưa thanh toán quá hạn (mặc định 3 ngày kể từ created_at).
     */
    public function pruneStalePendingDigitalOrders(): int
    {
        $cutoff = now()->subDays($this->pendingMaxAgeDays());
        $deleted = 0;

        Order::query()
            ->where('type', Order::TYPE_DIGITAL_PURCHASE)
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$deleted): void {
                foreach ($orders as $order) {
                    $order->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    /**
     * Độc giả hủy đơn tài liệu số đang chờ thanh toán (không áp dụng đơn đã paid / cancelled).
     */
    public function cancelPendingDigitalOrderForUser(string $publicId, int $userId): void
    {
        DB::transaction(function () use ($publicId, $userId): void {
            $order = Order::query()
                ->where('public_id', $publicId)
                ->where('user_id', $userId)
                ->where('type', Order::TYPE_DIGITAL_PURCHASE)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new \InvalidArgumentException(__('Không tìm thấy đơn hàng.'));
            }

            if ($order->status !== Order::STATUS_PENDING) {
                throw new \InvalidArgumentException(__('Chỉ có thể hủy đơn đang chờ thanh toán.'));
            }

            $order->update(['status' => Order::STATUS_CANCELLED]);
        });
    }

    /**
     * Đối soát đơn pending với SePay API (poll / kiểm tra thủ công).
     * Trả về trạng thái đơn sau khi thử khớp giao dịch.
     */
    public function syncPendingDigitalOrderPaymentFromSepay(Order $order): Order
    {
        if ($order->gateway !== Order::GATEWAY_SEPAY || $order->status !== Order::STATUS_PENDING) {
            return $order;
        }

        if (! $this->sepayTransactions->isConfigured()) {
            return $order;
        }

        $ref = trim((string) $order->merchant_reference);
        if ($ref === '') {
            return $order;
        }

        $minAmount = (int) $order->total_vnd_snapshot;
        $rows = $this->sepayTransactions->findIncomingTransactionsForPaymentCode($ref, $minAmount);

        foreach ($rows as $row) {
            $this->handleSepayWebhook($this->sepayTransactions->mapApiRowToWebhookPayload($row));
            $order->refresh();
            if ($order->status === Order::STATUS_PAID) {
                break;
            }
        }

        return $order->fresh() ?? $order;
    }

    /**
     * Đơn đã paid và mọi dòng unlock đều có entitlement hợp lệ cho user sở hữu đơn.
     */
    public function orderPdfEntitlementsAreActive(Order $order): bool
    {
        if ($order->status !== Order::STATUS_PAID || $order->user_id === null) {
            return false;
        }

        $userId = (int) $order->user_id;
        $items = $order->relationLoaded('items')
            ? $order->items
            : $order->items()->get();

        $unlockItems = $items->filter(
            fn (OrderItem $item) => $item->item_type === self::ITEM_TYPE_DIGITAL_UNLOCK && $item->digital_asset_id
        );

        if ($unlockItems->isEmpty()) {
            return false;
        }

        foreach ($unlockItems as $item) {
            if (! $this->paywall->userHasPdfDownloadEntitlement($userId, (int) $item->digital_asset_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Xử lý webhook SePay (idempotent theo transaction id).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleSepayWebhook(array $payload): void
    {
        $transferType = (string) ($payload['transferType'] ?? '');
        if ($transferType !== 'in') {
            return;
        }

        if (! $this->verifySepayDestinationAccount($payload)) {
            Log::notice('sepay.webhook.destination_account_blocked', [
                'sepay_id' => $payload['id'] ?? null,
            ]);

            return;
        }

        $primaryCode = $this->normalizeSepayCodeField($payload['code'] ?? null);
        $candidateRefs = array_values(array_unique(array_filter(array_merge(
            $primaryCode !== null ? [$primaryCode] : [],
            $this->extractSepayMerchantReferenceCandidates($payload)
        ))));

        if ($candidateRefs === []) {
            Log::notice('sepay.webhook.unresolved_merchant_reference', [
                'sepay_id' => $payload['id'] ?? null,
                'has_content' => isset($payload['content']) && is_string($payload['content']) && trim($payload['content']) !== '',
                'has_description' => isset($payload['description']) && is_string($payload['description']) && trim($payload['description']) !== '',
            ]);

            return;
        }

        $txId = $payload['id'] ?? null;
        if ($txId === null || $txId === '') {
            return;
        }
        $txId = (string) $txId;
        if (strlen($txId) > 120) {
            return;
        }

        $amount = $payload['transferAmount'] ?? null;
        if (! is_int($amount) && ! ctype_digit((string) $amount)) {
            return;
        }
        $amount = (int) $amount;

        DB::transaction(function () use ($payload, $candidateRefs, $txId, $amount, $primaryCode): void {
            // Ưu tiên trường `code` SePay gửi — tránh khớp nhầm đơn khi content chứa nhiều mã DL…
            $order = null;
            if ($primaryCode !== null) {
                $order = Order::query()
                    ->where('gateway', Order::GATEWAY_SEPAY)
                    ->where('merchant_reference', $primaryCode)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $order) {
                $order = Order::query()
                    ->where('gateway', Order::GATEWAY_SEPAY)
                    ->whereIn('merchant_reference', $candidateRefs)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $order) {
                Log::notice('sepay.webhook.order_not_found', [
                    'sepay_id' => $payload['id'] ?? null,
                    'candidate_count' => count($candidateRefs),
                ]);

                return;
            }

            if (PaymentTransaction::query()->where('idempotency_key', $txId)->exists()) {
                return;
            }

            if ($order->status === Order::STATUS_PAID) {
                $this->recordSepayPaymentTransaction($order, $payload, $txId, $amount, 'success');

                return;
            }

            $requiredAmount = (int) $order->total_vnd_snapshot;
            $callbackMeta = $this->buildSepayCallbackMeta($payload, (string) $order->merchant_reference, $amount);

            if ($amount < $requiredAmount) {
                $this->recordSepayPaymentTransaction($order, $payload, $txId, $amount, 'failed', $callbackMeta);
                if ($order->status === Order::STATUS_PENDING) {
                    $order->status = Order::STATUS_FAILED;
                    $order->save();
                }

                return;
            }

            // Vẫn ghi nhận paid khi user đã CK dù đơn cancelled/expired/failed (tránh mất tiền).
            $order->status = Order::STATUS_PAID;
            $order->paid_at = now();
            $order->save();

            $this->recordSepayPaymentTransaction($order, $payload, $txId, $amount, 'success', $callbackMeta);
            $this->grantPdfDownloadEntitlementsForOrder($order);
        });
    }

    /** Bổ sung entitlement nếu đơn đã paid nhưng thiếu quyền (sửa lệch trạng thái hiếm gặp). */
    public function reconcilePaidOrderEntitlements(Order $order): void
    {
        if ($order->status !== Order::STATUS_PAID) {
            return;
        }

        $this->grantPdfDownloadEntitlementsForOrder($order);
    }

    private function userPendingDigitalOrderCount(int $userId): int
    {
        return (int) Order::query()
            ->where('user_id', $userId)
            ->where('type', Order::TYPE_DIGITAL_PURCHASE)
            ->where('status', Order::STATUS_PENDING)
            ->count();
    }

    private function assertUserCanCreatePendingDigitalOrder(int $userId): void
    {
        $max = $this->pendingMaxCountPerUser();
        $count = $this->userPendingDigitalOrderCount($userId);
        if ($count >= $max) {
            throw new \RuntimeException(
                "Bạn đang có {$count} đơn chờ thanh toán (giới hạn {$max}). Vui lòng hoàn tất hoặc hủy bớt đơn cũ trước khi tạo đơn mới."
            );
        }
    }

    private function grantPdfDownloadEntitlementsForOrder(Order $order): void
    {
        $items = $order->relationLoaded('items')
            ? $order->items
            : $order->items()->get();

        $clearedAssetIds = [];

        foreach ($items as $item) {
            if ($item->item_type !== self::ITEM_TYPE_DIGITAL_UNLOCK || ! $item->digital_asset_id) {
                continue;
            }

            DigitalAssetPdfDownloadEntitlement::query()->updateOrCreate(
                [
                    'user_id' => (int) $order->user_id,
                    'digital_asset_id' => (int) $item->digital_asset_id,
                ],
                [
                    'order_id' => $order->id,
                    'granted_at' => now(),
                    'expires_at' => null,
                    'revoked_at' => null,
                ]
            );

            $clearedAssetIds[] = (int) $item->digital_asset_id;
        }

        if ($clearedAssetIds !== [] && $order->user_id) {
            $user = User::query()->find((int) $order->user_id);
            if ($user !== null) {
                $this->digitalCart->removeDigitalItems($user, $clearedAssetIds);
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $callbackMeta
     */
    private function recordSepayPaymentTransaction(
        Order $order,
        array $payload,
        string $txId,
        int $amount,
        string $status,
        ?array $callbackMeta = null
    ): void {
        if (PaymentTransaction::query()->where('idempotency_key', $txId)->exists()) {
            return;
        }

        try {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway' => Order::GATEWAY_SEPAY,
                'status' => $status,
                'amount_vnd' => $amount,
                'currency' => 'VND',
                'gateway_transaction_id' => (string) ($payload['referenceCode'] ?? $txId),
                'idempotency_key' => $txId,
                'verified_at' => $status === 'success' ? now() : null,
                'callback_meta' => $callbackMeta ?? $this->buildSepayCallbackMeta(
                    $payload,
                    (string) $order->merchant_reference,
                    $amount
                ),
            ]);
        } catch (Throwable $e) {
            Log::warning('sepay.webhook.payment_transaction_record_failed', [
                'order_id' => $order->id,
                'sepay_id' => $payload['id'] ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSepayCallbackMeta(array $payload, string $resolvedCode, int $amount): array
    {
        return [
            'sepay_id' => $payload['id'] ?? null,
            'gateway' => $payload['gateway'] ?? null,
            'transactionDate' => $payload['transactionDate'] ?? null,
            'accountNumber' => $payload['accountNumber'] ?? null,
            'referenceCode' => $payload['referenceCode'] ?? null,
            'content' => $payload['content'] ?? null,
            'code' => $resolvedCode,
            'transferAmount' => $amount,
        ];
    }

    /**
     * SePay có thể gửi `code` rỗng khi cấu hình "cấu trúc mã" trên my.sepay không khớp prefix/suffix app.
     * Khi đó vẫn có thể suy ra mã từ nội dung CK (content / description).
     *
     * @return list<string>
     */
    private function extractSepayMerchantReferenceCandidates(array $payload): array
    {
        $haystackParts = [];
        foreach (['content', 'description'] as $key) {
            $v = $payload[$key] ?? null;
            if (is_string($v) && trim($v) !== '') {
                $haystackParts[] = trim($v);
            }
        }

        if ($haystackParts === []) {
            return [];
        }

        $haystackUpper = mb_strtoupper(implode("\n", $haystackParts), 'UTF-8');
        $prefixes = $this->sepayMerchantReferencePrefixes();
        usort($prefixes, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $out = [];
        foreach ($prefixes as $prefix) {
            $p = mb_strtoupper(trim($prefix), 'UTF-8');
            if ($p === '') {
                continue;
            }

            $escaped = preg_quote($p, '/');
            if (preg_match_all('/'.$escaped.'([A-Z0-9]{10})/u', $haystackUpper, $m)) {
                foreach ($m[1] as $suffix) {
                    $out[] = $p.$suffix;
                }
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function sepayMerchantReferencePrefixes(): array
    {
        $defaults = [trim((string) env('SEPAY_PAYMENT_CODE_PREFIX', 'DL'))];
        $extraRaw = trim((string) env('SEPAY_WEBHOOK_PARSE_PREFIXES', ''));
        $list = $defaults;
        if ($extraRaw !== '') {
            foreach (array_map('trim', explode(',', $extraRaw)) as $segment) {
                if ($segment !== '') {
                    $list[] = $segment;
                }
            }
        }

        return array_values(array_unique(array_filter($list)));
    }

    private function normalizeSepayCodeField(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }
        $t = trim($raw);

        return $t === '' ? null : $t;
    }

    /**
     * Bật qua SEPAY_WEBHOOK_VERIFY_ACCOUNT_NUMBER=true để chặn webhook ghi có STK đích khác (nếu SePay gửi kèm).
     */
    private function verifySepayDestinationAccount(array $payload): bool
    {
        if (! filter_var(env('SEPAY_WEBHOOK_VERIFY_ACCOUNT_NUMBER', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $expected = preg_replace('/\s+/', '', (string) env('SEPAY_ACCOUNT_NUMBER', ''));
        $incoming = preg_replace('/\s+/', '', (string) ($payload['accountNumber'] ?? ''));

        if ($expected === '') {
            return true;
        }

        if ($incoming === '') {
            return false;
        }

        return strcasecmp($expected, $incoming) === 0;
    }

    private function generatePaymentCode(): string
    {
        $prefix = trim((string) env('SEPAY_PAYMENT_CODE_PREFIX', 'DL'));
        if ($prefix === '') {
            $prefix = 'DL';
        }

        // SePay yêu cầu code có cấu trúc theo config prefix; suffix nên khó đoán.
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = $prefix.strtoupper(Str::random(10));
            if (! Order::query()->where('merchant_reference', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Không tạo được mã thanh toán duy nhất. Vui lòng thử lại.');
    }
}
