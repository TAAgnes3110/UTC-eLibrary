<?php

declare(strict_types=1);

namespace App\Services\LibraryCard;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use App\Models\Loan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LibraryCardOverdueLockService
{
    private const SEVERE_OVERDUE_DAYS = 30;

    private const AUTO_LOCK_NOTE_PREFIX = '[AUTO] Khóa thẻ do chưa trả sách quá hạn';

    /**
     * Ngày hạn chót: phiếu có hạn trả trước mốc này được coi là quá hạn > {@see SEVERE_OVERDUE_DAYS} ngày (so với đầu ngày hôm nay).
     */
    public function severeOverdueDueDateBefore(): CarbonInterface
    {
        return now()->startOfDay()->subDays(self::SEVERE_OVERDUE_DAYS);
    }

    public function findSevereOverdueLoanForCard(LibraryCard $card): ?Loan
    {
        $before = $this->severeOverdueDueDateBefore();

        return Loan::query()
            ->where('library_card_id', $card->id)
            ->whereIn('status', [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $before)
            ->orderBy('due_date')
            ->first();
    }

    /**
     * Khóa các thẻ đang có phiếu quá hạn nặng; mở khóa thẻ chỉ bị khóa tự động trước đó khi không còn phiếu như vậy.
     *
     * @return array{locked:int, unlocked:int}
     */
    public function syncLocksForSevereOverdue(): array
    {
        $locked = 0;
        $unlocked = 0;

        $before = $this->severeOverdueDueDateBefore();

        $cardIdsToLock = Loan::query()
            ->whereIn('status', [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $before)
            ->distinct()
            ->pluck('library_card_id');

        foreach ($cardIdsToLock as $cardId) {
            DB::transaction(function () use ($cardId, &$locked, $before): void {
                $card = LibraryCard::query()->whereKey($cardId)->lockForUpdate()->first();
                if (! $card instanceof LibraryCard) {
                    return;
                }
                if ($card->status === LibraryCardStatus::LOCKED) {
                    return;
                }

                $oldest = Loan::query()
                    ->where('library_card_id', $card->id)
                    ->whereIn('status', [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', $before)
                    ->orderBy('due_date')
                    ->first();
                if (! $oldest instanceof Loan) {
                    return;
                }

                $today = now()->startOfDay();
                $overdueDays = (int) $oldest->due_date->diffInDays($today);

                $card->status = LibraryCardStatus::LOCKED;
                $lockReason = sprintf(
                    '%s %d ngày (loan_id=%d).',
                    self::AUTO_LOCK_NOTE_PREFIX,
                    $overdueDays,
                    (int) $oldest->id
                );
                $existingNotes = trim((string) ($card->notes ?? ''));
                if (! str_contains($existingNotes, (string) $oldest->id)) {
                    $card->notes = $existingNotes === '' ? $lockReason : $existingNotes.PHP_EOL.$lockReason;
                }
                $card->save();
                $locked++;
            });
        }

        $candidates = LibraryCard::query()
            ->where('status', LibraryCardStatus::LOCKED)
            ->where(function ($q) {
                $q->where('notes', 'like', '%'.self::AUTO_LOCK_NOTE_PREFIX.'%');
            })
            ->get();

        foreach ($candidates as $card) {
            if ($this->findSevereOverdueLoanForCard($card) instanceof Loan) {
                continue;
            }
            DB::transaction(function () use ($card, &$unlocked): void {
                $fresh = LibraryCard::query()->whereKey($card->id)->lockForUpdate()->first();
                if (! $fresh instanceof LibraryCard || $fresh->status !== LibraryCardStatus::LOCKED) {
                    return;
                }
                if (! str_contains((string) ($fresh->notes ?? ''), self::AUTO_LOCK_NOTE_PREFIX)) {
                    return;
                }
                if ($this->findSevereOverdueLoanForCard($fresh) instanceof Loan) {
                    return;
                }
                $fresh->status = LibraryCardStatus::ACTIVE;
                $fresh->save();
                $unlocked++;
            });
        }

        return ['locked' => $locked, 'unlocked' => $unlocked];
    }
}
