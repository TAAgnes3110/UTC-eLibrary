<?php

namespace App\Http\Controllers\Api\Me;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalPaymentOrderStoreRequest;
use App\Models\DigitalAsset;
use App\Services\DigitalPaymentOrderService;
use Illuminate\Http\JsonResponse;

class DigitalPaymentOrderController extends Controller
{
    public function __construct(
        private readonly DigitalPaymentOrderService $orders
    ) {}

    public function store(DigitalPaymentOrderStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $ids = $request->uniqueDigitalAssetIds();
        $assets = DigitalAsset::query()->whereIn('id', $ids)->get();

        if ($assets->count() !== count($ids)) {
            return ApiResponse::error('Một hoặc nhiều tài liệu không tồn tại.', 404);
        }

        try {
            $order = $this->orders->createPendingOrderForDigitalAssets((int) $user->id, $assets);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage() ?: 'Không tạo được giao dịch.', 422);
        }

        return ApiResponse::success([
            'order' => [
                'id' => (int) $order->id,
                'public_id' => (string) $order->public_id,
                'status' => (string) $order->status,
                'amount_vnd' => (int) $order->total_vnd_snapshot,
                'currency' => (string) $order->currency,
                'merchant_reference' => (string) $order->merchant_reference,
                'price_locked_until' => $order->price_locked_until?->toIso8601String(),
                'gateway' => (string) $order->gateway,
                'qr_image_url' => (string) data_get($order->gateway_init_payload, 'qr_image_url', ''),
            ],
        ], __('Đã tạo giao dịch. Vui lòng quét QR để thanh toán.'), 201);
    }
}
