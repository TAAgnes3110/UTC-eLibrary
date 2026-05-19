<?php

namespace App\Console\Commands;

use App\Services\Notifications\LoanDueSoonNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyLoansDueSoonCommand extends Command
{
    protected $signature = 'loans:notify-due-soon
                            {--days= : Số ngày báo trước (mặc định LOAN_DUE_SOON_DAYS_BEFORE trong .env)}';

    protected $description = 'Gửi thông báo phiếu mượn sắp đến hạn cho bạn đọc và digest cho staff';

    public function handle(LoanDueSoonNotificationService $notifier): int
    {

        if (! config('notifications.loan_due_soon_enabled', true)) {

            $this->warn('Đã tắt nhắc sắp đến hạn (LOAN_DUE_SOON_NOTIFY_ENABLED=false).');

            return self::SUCCESS;

        }

        $daysOption = $this->option('days');

        $daysBefore = $daysOption !== null && $daysOption !== ''

            ? (int) $daysOption

            : null;

        $count = $notifier->notifyForLoansDueSoon(Carbon::today(), $daysBefore);

        $configuredDays = $daysBefore ?? (int) config('notifications.loan_due_soon_days_before', 2);

        $this->info("Đã xử lý {$count} phiếu sắp đến hạn (báo trước {$configuredDays} ngày).");

        return self::SUCCESS;

    }
}
