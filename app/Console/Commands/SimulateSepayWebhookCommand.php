<?php

namespace App\Console\Commands;

use App\Services\DigitalPaymentOrderService;
use Illuminate\Console\Command;

/**
 * Mô phỏng webhook SePay khi dev trên localhost (SePay không POST được vào máy local).
 */
class SimulateSepayWebhookCommand extends Command
{
    protected $signature = 'sepay:simulate-webhook
                            {code : Mã tham chiếu đơn (merchant_reference), ví dụ DL7V48ZTQI7K}
                            {amount : Số tiền VND đã chuyển, ví dụ 3000}
                            {--id= : SePay transaction id giả (mặc định random)}';

    protected $description = 'Mô phỏng webhook SePay (chỉ APP_ENV=local) để xác nhận đơn pending';

    public function handle(DigitalPaymentOrderService $orders): int
    {
        if (! app()->environment('local')) {
            $this->error('Lệnh này chỉ chạy khi APP_ENV=local.');

            return self::FAILURE;
        }

        $code = strtoupper(trim((string) $this->argument('code')));
        $amount = (int) $this->argument('amount');
        if ($code === '' || $amount <= 0) {
            $this->error('code và amount phải hợp lệ.');

            return self::FAILURE;
        }

        $txId = $this->option('id');
        if ($txId === null || $txId === '') {
            $txId = (string) random_int(900_000_000, 999_999_999);
        }

        $payload = [
            'id' => (int) $txId,
            'gateway' => 'SIMULATE',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => env('SEPAY_ACCOUNT_NUMBER', ''),
            'code' => null,
            'content' => 'Mo phong CK '.$code,
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'SIM'.$txId,
        ];

        $orders->handleSepayWebhook($payload);

        $this->info("Đã gửi payload mô phỏng cho mã {$code}, số tiền {$amount} VND.");
        $this->line('Kiểm tra DB: orders.status = paid, hoặc bấm «Kiểm tra thủ công» trên trình duyệt.');

        return self::SUCCESS;
    }
}
