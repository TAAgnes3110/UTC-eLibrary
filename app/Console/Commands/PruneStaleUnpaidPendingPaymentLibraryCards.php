<?php

namespace App\Console\Commands;

use App\Models\LibraryCard;
use App\Services\LibraryCard\LibraryCardManagementService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Xóa thẻ ở trạng thái chờ thanh toán quá hạn (sau khi đã gọi {@see LibraryCardManagementService::setPendingPaymentDeadline}).
 */
class PruneStaleUnpaidPendingPaymentLibraryCards extends Command
{
    protected $signature = 'library-cards:prune-stale-pending-payment';

    protected $description = 'Xóa library_cards: pending_payment, chưa paid, đã quá params.payment_due_at';

    public function handle(): int
    {
        $now = Carbon::now();

        $candidates = LibraryCard::query()
            ->where('workflow_status', LibraryCard::WORKFLOW_PENDING_PAYMENT)
            ->with('payment')
            ->get();

        $toDelete = [];
        foreach ($candidates as $card) {
            $payment = $card->payment;
            if ($payment !== null && $payment->payment_status === LibraryCard::PAYMENT_PAID) {
                continue;
            }

            $dueRaw = data_get($card->params, 'payment_due_at');
            if ($dueRaw === null || $dueRaw === '') {
                continue;
            }
            $due = Carbon::parse((string) $dueRaw);
            if ($due->gt($now)) {
                continue;
            }

            $toDelete[] = $card->id;
        }

        $deleted = 0;
        if ($toDelete !== []) {
            DB::transaction(function () use ($toDelete, &$deleted) {
                foreach (LibraryCard::query()->whereKey($toDelete)->get() as $card) {
                    $card->forceDelete();
                    $deleted++;
                }
            });
        }

        $this->info("Đã xóa vĩnh viễn {$deleted} bản ghi.");

        return self::SUCCESS;
    }
}
