<?php

namespace App\Services;

class SepayQrService
{
    public function buildQrImageUrl(int $amountVnd, string $description): string
    {
        $acc = trim((string) env('SEPAY_ACCOUNT_NUMBER', ''));
        $bank = trim((string) env('SEPAY_BANK_NAME', ''));
        if ($acc === '' || $bank === '') {
            throw new \RuntimeException('Chưa cấu hình SEPAY_ACCOUNT_NUMBER / SEPAY_BANK_NAME.');
        }

        $amountVnd = max(0, $amountVnd);
        $des = rawurlencode($description);
        $accEnc = rawurlencode($acc);
        $bankEnc = rawurlencode($bank);

        return "https://qr.sepay.vn/img?acc={$accEnc}&bank={$bankEnc}&amount={$amountVnd}&des={$des}";
    }
}
