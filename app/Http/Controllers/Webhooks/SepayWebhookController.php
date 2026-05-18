<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\DigitalPaymentOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SepayWebhookController extends Controller
{
    public function __construct(
        private readonly DigitalPaymentOrderService $orders
    ) {}

    /**
     * SePay yêu cầu response đúng body: {"success": true}
     */
    public function handle(Request $request): JsonResponse
    {
        $secret = trim((string) config('services.sepay.webhook_secret', ''));
        if ($secret === '') {
            if (app()->environment('production')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
        } else {
            $incoming = trim((string) ($request->bearerToken() ?? $request->header('X-Sepay-Webhook-Token', '')));
            if ($incoming === '' || ! hash_equals($secret, $incoming)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->all();

        try {
            $this->orders->handleSepayWebhook(is_array($payload) ? $payload : []);
        } catch (\Throwable $e) {
            // Dù lỗi nội bộ, vẫn trả success để tránh SePay retry spam; giao dịch có thể đối soát lại.
            report($e);
        }

        return response()->json(['success' => true], 200);
    }
}
