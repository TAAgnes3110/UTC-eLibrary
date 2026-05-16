<?php

namespace App\Http\Controllers\Api\Me;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DigitalPaymentOrderService;
use App\Services\SepayTransactionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderStatusController extends Controller
{
    public function __construct(
        private readonly DigitalPaymentOrderService $digitalPaymentOrders,
        private readonly SepayTransactionApiService $sepayTransactions
    ) {}

    public function show(Request $request, string $publicId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $order = Order::query()
            ->with(['items.digitalAsset.book'])
            ->where('public_id', $publicId)
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $order) {
            return ApiResponse::notFound(__('Không tìm thấy giao dịch.'));
        }

        $syncedFromSepay = false;
        if ($request->boolean('sync') && $order->status === 'pending' && $order->gateway === 'sepay') {
            $order = $this->digitalPaymentOrders->syncPendingDigitalOrderPaymentFromSepay($order);
            $syncedFromSepay = true;
        }

        $order->refresh();
        if ($order->status === 'paid' && ! $this->digitalPaymentOrders->orderPdfEntitlementsAreActive($order)) {
            DB::transaction(function () use ($order): void {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();
                if ($locked !== null && $locked->status === 'paid') {
                    $this->digitalPaymentOrders->reconcilePaidOrderEntitlements($locked);
                }
            });
            $order->refresh();
        }

        $entitlementsGranted = $this->digitalPaymentOrders->orderPdfEntitlementsAreActive($order);

        $qrImageUrl = $order->status === 'pending'
            ? (string) (data_get($order->gateway_init_payload, 'qr_image_url') ?? '')
            : '';

        return ApiResponse::success([
            'order' => [
                'public_id' => (string) $order->public_id,
                'status' => (string) $order->status,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
                'amount_vnd' => (int) $order->total_vnd_snapshot,
                'currency' => (string) $order->currency,
                'merchant_reference' => (string) $order->merchant_reference,
                'qr_image_url' => $qrImageUrl !== '' ? $qrImageUrl : null,
            ],
            'items' => $order->items->map(static function ($item): array {
                $book = $item->digitalAsset?->book;

                return [
                    'digital_asset_id' => (int) $item->digital_asset_id,
                    'book_id' => (int) (data_get($item->meta, 'book_id') ?: $book?->id ?: 0),
                    'title' => (string) (data_get($item->meta, 'book_title') ?: $book?->title ?: 'Tài liệu số'),
                    'unit_price_vnd' => (int) $item->unit_price_vnd_snapshot,
                    'line_total_vnd' => (int) $item->line_total_vnd_snapshot,
                ];
            })->values()->all(),
            'entitlements_granted' => $entitlementsGranted,
            'synced_from_sepay' => $syncedFromSepay,
            'sepay_sync_available' => $this->sepayTransactions->isConfigured(),
        ]);
    }

    public function cancel(Request $request, string $publicId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        try {
            $this->digitalPaymentOrders->cancelPendingDigitalOrderForUser($publicId, (int) $user->id);
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            $status = str_contains($message, 'Không tìm thấy') ? 404 : 422;

            return ApiResponse::error($message, $status);
        }

        return ApiResponse::success(null, __('Đã hủy đơn hàng.'));
    }
}
