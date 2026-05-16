<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Đối soát giao dịch qua SePay API v2 (khi webhook chưa tới hoặc dev localhost).
 *
 * @see https://developer.sepay.vn/en/sepay-api/v2/giao-dich/danh-sach
 */
class SepayTransactionApiService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.sepay.api_token', '')) !== '';
    }

    /**
     * Tìm giao dịch tiền vào khớp mã thanh toán (merchant_reference).
     *
     * @return list<array<string, mixed>>
     */
    public function findIncomingTransactionsForPaymentCode(string $paymentCode, int $minAmountVnd): array
    {
        $paymentCode = trim($paymentCode);
        if ($paymentCode === '' || ! $this->isConfigured()) {
            return [];
        }

        $baseUrl = rtrim((string) config('services.sepay.api_base_url', 'https://userapi.sepay.vn/v2'), '/');
        $token = (string) config('services.sepay.api_token');

        $from = now()->subDays((int) config('services.sepay.sync_lookback_days', 3))->format('Y-m-d H:i:s');
        $to = now()->format('Y-m-d H:i:s');

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->acceptJson()
                ->get("{$baseUrl}/transactions", [
                    'q' => $paymentCode,
                    'transfer_type' => 'in',
                    'amount_in_min' => max(0, $minAmountVnd),
                    'transaction_date_from' => $from,
                    'transaction_date_to' => $to,
                    'per_page' => 50,
                    'transaction_date_sort' => 'desc',
                ]);
        } catch (\Throwable $e) {
            Log::warning('sepay.api.transactions_request_failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('sepay.api.transactions_http_error', [
                'status' => $response->status(),
            ]);

            return [];
        }

        $body = $response->json();
        if (! is_array($body) || ($body['status'] ?? '') !== 'success') {
            return [];
        }

        $rows = $body['data'] ?? [];
        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (($row['transfer_type'] ?? '') !== 'in') {
                continue;
            }
            $amountIn = (int) ($row['amount_in'] ?? 0);
            if ($amountIn < $minAmountVnd) {
                continue;
            }
            if (! $this->rowMatchesPaymentCode($row, $paymentCode)) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Chuyển bản ghi API v2 sang payload webhook legacy để tái dùng handleSepayWebhook.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function mapApiRowToWebhookPayload(array $row): array
    {
        return [
            'id' => $row['id'] ?? null,
            'gateway' => (string) ($row['bank_brand_name'] ?? ''),
            'transactionDate' => (string) ($row['transaction_date'] ?? ''),
            'accountNumber' => (string) ($row['account_number'] ?? ''),
            'code' => $row['code'] ?? null,
            'content' => (string) ($row['transaction_content'] ?? ''),
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => (int) ($row['amount_in'] ?? 0),
            'referenceCode' => (string) ($row['reference_number'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowMatchesPaymentCode(array $row, string $paymentCode): bool
    {
        $code = isset($row['code']) && is_string($row['code']) ? strtoupper(trim($row['code'])) : '';
        $target = strtoupper($paymentCode);
        if ($code !== '' && $code === $target) {
            return true;
        }

        $content = strtoupper((string) ($row['transaction_content'] ?? ''));
        if ($content !== '' && str_contains($content, $target)) {
            return true;
        }

        return false;
    }
}
