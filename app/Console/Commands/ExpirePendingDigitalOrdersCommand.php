<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\DigitalPaymentOrderService;
use Illuminate\Console\Command;

class ExpirePendingDigitalOrdersCommand extends Command
{
    protected $signature = 'digital-orders:expire-pending';

    protected $description = 'Expire đơn pending quá hạn giá QR; xóa đơn pending chưa thanh toán quá 3 ngày';

    public function handle(DigitalPaymentOrderService $digitalPaymentOrders): int
    {
        $expired = Order::query()
            ->where('type', Order::TYPE_DIGITAL_PURCHASE)
            ->where('status', Order::STATUS_PENDING)
            ->whereNotNull('price_locked_until')
            ->where('price_locked_until', '<', now())
            ->update(['status' => Order::STATUS_EXPIRED]);

        $deleted = $digitalPaymentOrders->pruneStalePendingDigitalOrders();

        $this->info("Đã expire {$expired} đơn pending (hết hạn giá QR).");
        $this->info("Đã xóa {$deleted} đơn pending quá {$digitalPaymentOrders->pendingMaxAgeDays()} ngày.");

        return self::SUCCESS;
    }
}
