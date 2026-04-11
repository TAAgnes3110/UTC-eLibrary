<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\LoanItemCondition;
use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\LoanPolicy;
use App\Services\LoanPoliciesService;
use RuntimeException;

class LoanHelper
{
    public function __construct(
        private LoanPoliciesService $loanPoliciesService
    ) {}

    /**
     * Gom số lượng theo book_id từ danh sách loan items.
     * Dùng cho cả chiều trừ kho (mượn) và cộng kho (trả/hủy).
     *
     * @param  iterable<LoanItem>  $items
     * @return array<int, int>
     */
    public function aggregateBookDeltas(iterable $items): array
    {
        $bookDeltas = [];
        foreach ($items as $item) {
            $bookDeltas[$item->book_id] = ($bookDeltas[$item->book_id] ?? 0) + (int) $item->quantity;
        }

        return $bookDeltas;
    }

    /**
     * Chuẩn hóa payload cập nhật một dòng trả sách:
     * - condition_on_return lấy theo dòng, fallback theo request chung.
     * - fine_amount lấy max giữa nhập tay và mức hệ thống tự tính.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $linePayload
     * @return array{condition_on_return:?string,fine_amount:float}
     */
    public function buildReturnItemPayload(
        array $data,
        ?array $linePayload,
        Loan $loan,
        LoanItem $item,
        LoanPolicy $policy
    ): array {
        $conditionOnReturn = is_array($linePayload) && array_key_exists('condition_on_return', $linePayload)
            ? $this->normalizeCondition($linePayload['condition_on_return'])
            : $this->normalizeCondition($data['condition_on_return'] ?? null);
        $manualFineAmount = is_array($linePayload) && array_key_exists('fine_amount', $linePayload)
            ? (float) $linePayload['fine_amount']
            : (float) ($data['fine_amount'] ?? 0);
        $calculatedFineAmount = $this->calculateFineAmount($loan, $item, $policy, $conditionOnReturn);

        return [
            'condition_on_return' => $conditionOnReturn,
            'fine_amount' => max(0, max($manualFineAmount, $calculatedFineAmount)),
        ];
    }

    /**
     * Chuẩn hóa tình trạng sách theo enum lưu DB (tot/hong/mat).
     */
    public function normalizeCondition(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $enum = LoanItemCondition::tryFrom((string) $value);
        if ($enum === null) {
            throw new RuntimeException('Tình trạng sách không hợp lệ. Chỉ chấp nhận: tot, hong, mat.');
        }

        return $enum->value;
    }

    /**
     * Đếm số lượng sách đang mượn về nhà theo nhóm giáo trình/tham khảo và tổng.
     *
     * @return array{textbook:int,reference:int,total:int}
     */
    public function currentHomeBorrowCounts(LibraryCard $card): array
    {
        $existingByType = LoanItem::query()
            ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
            ->join('books', 'loan_items.book_id', '=', 'books.id')
            ->where('loans.library_card_id', $card->id)
            ->where('loans.loan_type', Loan::TYPE_HOME)
            ->whereIn('loans.status', [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE])
            ->selectRaw('books.resource_type as resource_type, COALESCE(SUM(loan_items.quantity), 0) as total_quantity')
            ->groupBy('books.resource_type')
            ->pluck('total_quantity', 'resource_type');

        $textbook = (int) ($existingByType[ResourceType::TEXTBOOK->value] ?? 0);
        $reference = (int) ($existingByType[ResourceType::REFERENCE->value] ?? 0);

        return [
            'textbook' => $textbook,
            'reference' => $reference,
            'total' => $textbook + $reference,
        ];
    }

    /**
     * Kiểm tra vượt hạn mức mượn về nhà theo policy của loại thẻ hiện tại.
     */
    public function assertCanBorrowHome(
        LibraryCard $card,
        int $newTextbook,
        int $newReference,
        int $newTotal
    ): void {
        $current = $this->currentHomeBorrowCounts($card);
        $limits = $this->loanPoliciesService->getBorrowLimitsForHolderType((string) $card->holder_type);

        if (($current['textbook'] + $newTextbook) > $limits['max_textbooks']) {
            throw new RuntimeException('Vượt số lượng giáo trình tối đa theo chính sách mượn');
        }
        if (($current['reference'] + $newReference) > $limits['max_reference']) {
            throw new RuntimeException('Vượt số lượng tài liệu tham khảo tối đa theo chính sách mượn');
        }
        if (($current['total'] + $newTotal) > $limits['max_books']) {
            throw new RuntimeException('Vượt số lượng sách tối đa theo chính sách mượn');
        }
    }

    /**
     * Trả về toàn bộ giới hạn mượn theo thẻ (tổng, giáo trình, tham khảo).
     *
     * @return array{max_books:int,max_textbooks:int,max_reference:int}
     */
    public function getBorrowLimitsForCard(LibraryCard $card): array
    {
        $limits = $this->loanPoliciesService->getBorrowLimitsForHolderType((string) $card->holder_type);

        return [
            'max_books' => (int) ($limits['max_books'] ?? 0),
            'max_textbooks' => (int) ($limits['max_textbooks'] ?? 0),
            'max_reference' => (int) ($limits['max_reference'] ?? 0),
        ];
    }

    /**
     * Lấy policy theo thẻ và đảm bảo luôn có policy hợp lệ.
     */
    public function resolvePolicyForCard(LibraryCard $card): LoanPolicy
    {
        $policy = $this->loanPoliciesService->resolvePolicyForCard($card);
        if (! $policy instanceof LoanPolicy) {
            throw new RuntimeException('Không tìm thấy chính sách mượn cho loại thẻ hiện tại');
        }

        return $policy;
    }

    /**
     * Shortcut lấy max tổng sách theo thẻ.
     */
    public function getMaxBooksForCard(LibraryCard $card): int
    {
        return $this->getBorrowLimitsForCard($card)['max_books'];
    }

    /**
     * Shortcut lấy max giáo trình theo thẻ.
     */
    public function getMaxTextbooksForCard(LibraryCard $card): int
    {
        return $this->getBorrowLimitsForCard($card)['max_textbooks'];
    }

    /**
     * Shortcut lấy max tài liệu tham khảo theo thẻ.
     */
    public function getMaxReferenceForCard(LibraryCard $card): int
    {
        return $this->getBorrowLimitsForCard($card)['max_reference'];
    }

    /**
     * Trừ tồn kho theo delta từng đầu sách (kèm lock hàng để đảm bảo nhất quán dữ liệu).
     *
     * @param  array<int, int>  $bookDeltas
     */
    public function deductBooksByDeltas(array $bookDeltas): void
    {
        // TODO: Khi chuyển sang BookCopy/barcode, đổi sang lock + trừ theo từng bản sao thay vì quantity của Book.
        if ($bookDeltas === []) {
            return;
        }

        $books = Book::query()
            ->whereIn('id', array_keys($bookDeltas))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($bookDeltas as $bookId => $delta) {
            $book = $books->get($bookId);
            if (! $book instanceof Book) {
                throw new RuntimeException('Sách không tồn tại trong hệ thống');
            }
            if ($book->quantity < $delta) {
                throw new RuntimeException('Số lượng sách không đủ');
            }
            $book->quantity -= $delta;
            $book->save();
        }
    }

    /**
     * Cộng tồn kho theo delta từng đầu sách (khi trả/hủy phiếu).
     *
     * @param  array<int, int>  $bookDeltas
     */
    public function restockBooksByDeltas(array $bookDeltas): void
    {
        // TODO: Khi có ledger lịch sử kho, ghi movement nhập/trả ngay tại đây để phục vụ audit.
        if ($bookDeltas === []) {
            return;
        }

        $books = Book::query()
            ->whereIn('id', array_keys($bookDeltas))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($bookDeltas as $bookId => $delta) {
            $book = $books->get($bookId);
            if (! $book instanceof Book) {
                throw new RuntimeException('Sách không tồn tại trong hệ thống');
            }
            $book->quantity += $delta;
            $book->save();
        }
    }

    /**
     * Tính tiền phạt cho một dòng mượn:
     * - Phạt trễ hạn theo ngày.
     * - Phạt hư hỏng theo phần trăm giá.
     * - Phạt mất sách theo hệ số giá + phí xử lý.
     */
    private function calculateFineAmount(Loan $loan, LoanItem $item, LoanPolicy $policy, ?string $conditionOnReturn): float
    {
        $overdueFinePerDay = max(0, (float) ($policy->overdue_fine_per_day ?? 0));
        $overdueDays = 0;
        if ($loan->due_date !== null && $loan->return_date !== null && $loan->return_date->gt($loan->due_date)) {
            $overdueDays = (int) $loan->due_date->diffInDays($loan->return_date);
        }

        $overdueFine = $overdueDays * $overdueFinePerDay * (int) $item->quantity;

        $bookPrice = max(0, (float) ($item->book?->price ?? 0));
        $params = is_array($policy->params) ? $policy->params : [];
        $damagePercent = max(0, (float) ($params['damage_fine_percent'] ?? 0.3));
        $lossMultiplier = max(1, (float) ($params['loss_fine_multiplier'] ?? 2));
        $processingFee = max(0, (float) ($params['replacement_processing_fee'] ?? 10000));

        $conditionFine = 0.0;
        if ($conditionOnReturn === LoanItemCondition::DAMAGED->value) {
            $conditionFine = $bookPrice * $damagePercent * (int) $item->quantity;
        } elseif ($conditionOnReturn === LoanItemCondition::LOST->value) {
            $conditionFine = (($bookPrice * $lossMultiplier) + $processingFee) * (int) $item->quantity;
        }

        return round($overdueFine + $conditionFine, 2);
    }
}
