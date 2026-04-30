<?php

namespace App\Console\Commands;

use App\Services\Notifications\LoanDueSoonNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyLoansDueSoonCommand extends Command
{
    protected $signature = 'loans:notify-due-soon';

    protected $description = 'Gửi thông báo phiếu mượn sắp đến hạn (còn 2 ngày) cho bạn đọc và digest cho staff';

    public function handle(LoanDueSoonNotificationService $notifier): int
    {
        $count = $notifier->notifyForLoansDueInTwoDays(Carbon::today());
        $this->info("Đã xử lý {$count} phiếu sắp đến hạn (nhắc bạn đọc + digest admin).");

        return self::SUCCESS;
    }
}
