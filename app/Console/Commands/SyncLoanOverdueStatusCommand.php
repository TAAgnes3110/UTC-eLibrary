<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;

use App\Models\Loan;
use App\Services\Notifications\LoanOverdueNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLoanOverdueStatusCommand extends Command
{
    protected $signature = 'loans:sync-overdue';

    protected $description = 'Cập nhật phiếu đang mượn sang quá hạn khi đã qua ngày hẹn trả và gửi thông báo';

    public function handle(LoanOverdueNotificationService $notifier): int
    {
        $ids = Loan::query()
            ->where('status', LoanStatus::BORROWED)
            ->whereNull('return_date')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->pluck('id');

        $count = 0;
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $notifier, &$count): void {
                $loan = Loan::query()->whereKey($id)->lockForUpdate()->first();
                if (! $loan instanceof Loan) {
                    return;
                }
                if ($loan->status !== LoanStatus::BORROWED || $loan->return_date !== null) {
                    return;
                }
                if ($loan->due_date === null || $loan->due_date->toDateString() >= now()->toDateString()) {
                    return;
                }

                $loan->status = LoanStatus::OVERDUE;
                $loan->save();
                $notifier->notifyReaderForLoan($loan->fresh(['libraryCard']));
                $count++;
            });
        }

        $openOverdue = (int) Loan::query()
            ->where('status', LoanStatus::OVERDUE)
            ->whereNull('return_date')
            ->count();
        $notifier->notifyAdminsOverdueSummary($openOverdue);

        $this->info("Đã cập nhật {$count} phiếu sang quá hạn.");

        return self::SUCCESS;
    }
}
