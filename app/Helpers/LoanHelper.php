<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\BookPhysicalCondition;
use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Enums\LoanItemCondition;
use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\LibraryCard;
use App\Models\LibrarySetting;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\LoanPolicy;
use App\Services\LibrarySettingsService;
use App\Services\LoanPoliciesService;
use App\Services\StorageQuantitySyncService;
use Illuminate\Support\Str;
use RuntimeException;

class LoanHelper
{
    private const SEVERE_OVERDUE_DAYS = 30;

    public function __construct(
        private LoanPoliciesService $loanPoliciesService,
        private StorageQuantitySyncService $storageQuantitySyncService,
        private LibrarySettingsService $librarySettings
    ) {}

    /**
     * Lý do thẻ không đủ điều kiện mượn; null nếu được phép mượn.
     *
     * @return 'locked'|'not_eligible'|null
     */
    public function borrowEligibilityBlockReason(LibraryCard $card): ?string
    {
        $workflow = LibraryCard::normalizeWorkflowStatus(
            $card->workflow_status instanceof \BackedEnum
                ? $card->workflow_status->value
                : (string) $card->workflow_status
        );
        if ($workflow !== LibraryCard::WORKFLOW_ACTIVE) {
            return 'not_eligible';
        }

        $cardStatus = $card->status instanceof LibraryCardStatus
            ? $card->status
            : LibraryCardStatus::tryFrom((int) $card->status);
        if ($cardStatus === LibraryCardStatus::LOCKED) {
            return 'locked';
        }
        if ($cardStatus !== LibraryCardStatus::ACTIVE) {
            return 'not_eligible';
        }

        if ($card->expiry_date !== null && $card->expiry_date->startOfDay()->lt(now()->startOfDay())) {
            return 'not_eligible';
        }

        if ($this->cardHasSevereOverdueOpenLoan($card)) {
            return 'not_eligible';
        }

        return null;
    }

    /**
     * Kiểm tra thẻ trước khi lập phiếu mượn (quầy / duyệt yêu cầu).
     */
    public function assertCardEligibleForBorrow(LibraryCard $card): void
    {
        $reason = $this->borrowEligibilityBlockReason($card);
        if ($reason === 'locked') {
            throw new RuntimeException('Thẻ đang bị khóa, không thể mượn thêm');
        }
        if ($reason === 'not_eligible') {
            if ($card->expiry_date !== null && $card->expiry_date->startOfDay()->lt(now()->startOfDay())) {
                throw new RuntimeException('Thẻ thư viện đã hết hạn, không thể mượn thêm');
            }
            if ($this->cardHasSevereOverdueOpenLoan($card)) {
                throw new RuntimeException('Thẻ có phiếu mượn quá hạn trên 30 ngày chưa trả, không thể mượn thêm');
            }

            throw new RuntimeException('Thẻ chưa ở trạng thái được phép mượn (chưa kích hoạt hoặc đã ngưng).');
        }
    }

    private function cardHasSevereOverdueOpenLoan(LibraryCard $card): bool
    {
        $before = now()->startOfDay()->subDays(self::SEVERE_OVERDUE_DAYS);

        return Loan::query()
            ->where('library_card_id', $card->id)
            ->where('deleted', false)
            ->whereIn('status', [LoanStatus::BORROWED, LoanStatus::OVERDUE])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $before)
            ->exists();
    }

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
     * @return array{condition_on_return:?string,damage_percent:?int,fine_amount:float}
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
        $damageSeverityPercent = $this->resolveDamageSeverityPercent($conditionOnReturn, $linePayload, $data);
        $calculatedFineAmount = $this->calculateFineAmount(
            $loan,
            $item,
            $policy,
            $conditionOnReturn,
            $damageSeverityPercent
        );
        $manualFineAmount = is_array($linePayload) && array_key_exists('fine_amount', $linePayload)
            ? (float) $linePayload['fine_amount']
            : (float) ($data['fine_amount'] ?? 0);
        $fineAmount = max($manualFineAmount, $calculatedFineAmount);

        return [
            'condition_on_return' => $conditionOnReturn,
            'damage_percent' => $damageSeverityPercent,
            'fine_amount' => max(0, round($fineAmount, 2)),
        ];
    }

    /**
     * Snapshot chính sách phạt cho màn trả sách (FE tự tính).
     *
     * @return array<string, float|string>
     */
    /**
     * Giá bìa dùng tính phạt: ưu tiên giá chốt lúc mượn, fallback giá sách hiện tại (phiếu cũ).
     */
    public function resolveBookPriceForLoanItem(LoanItem $item): float
    {
        if ($item->book_price_at_loan !== null) {
            return max(0, (float) $item->book_price_at_loan);
        }

        return max(0, (float) ($item->book?->price ?? 0));
    }

    public function finePolicySnapshot(LoanPolicy $policy): array
    {
        $params = is_array($policy->params) ? $policy->params : [];

        return [
            'damage_fine_percent' => max(0, (float) ($params['damage_fine_percent'] ?? 0.1)),
            'loss_fine_multiplier' => max(1, (float) ($params['loss_fine_multiplier'] ?? 2)),
            'replacement_processing_fee' => max(0, (float) ($params['replacement_processing_fee'] ?? 10000)),
            'overdue_fine_per_day' => max(0, (float) ($policy->overdue_fine_per_day ?? 0)),
            'late_return_fine_mode' => $this->librarySettings->getLateReturnFineMode(),
            'late_return_fine_percent_of_book' => $this->librarySettings->getLateReturnFinePercentOfBook(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $linePayload
     */
    private function resolveDamageSeverityPercent(?string $conditionOnReturn, ?array $linePayload, array $data = []): ?int
    {
        if ($conditionOnReturn === LoanItemCondition::LOST->value) {
            return 100;
        }
        if ($conditionOnReturn !== LoanItemCondition::DAMAGED->value) {
            return null;
        }
        if (is_array($linePayload) && array_key_exists('damage_percent', $linePayload)) {
            return $this->normalizeDamageSeverityPercent($linePayload['damage_percent']);
        }
        if (array_key_exists('damage_percent', $data)) {
            return $this->normalizeDamageSeverityPercent($data['damage_percent']);
        }

        throw new RuntimeException('Vui lòng nhập % hư hỏng (1–100) khi sách hư hỏng.');
    }

    public function normalizeDamageSeverityPercent(mixed $value): int
    {
        if ($value === null || $value === '') {
            throw new RuntimeException('Vui lòng nhập % hư hỏng (1–100) khi sách hư hỏng.');
        }
        $n = (int) round((float) $value);
        if ($n < 1 || $n > 100) {
            throw new RuntimeException('% hư hỏng phải từ 1 đến 100.');
        }

        return $n;
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
     * Đếm số lượng sách đang mượn theo nhóm giáo trình/tham khảo và tổng.
     * Dùng cho hạn mức theo chính sách thẻ.
     *
     * @return array{textbook:int,reference:int,total:int}
     */
    public function currentOutstandingBorrowCounts(LibraryCard $card): array
    {
        $existingByType = LoanItem::query()
            ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
            ->join('books', 'loan_items.book_id', '=', 'books.id')
            ->where('loans.library_card_id', $card->id)
            ->where('loans.deleted', false)
            ->whereIn('loans.loan_type', [LoanType::HOME, LoanType::ONSITE])
            ->whereIn('loans.status', [LoanStatus::BORROWED, LoanStatus::OVERDUE])
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
     * Kiểm tra vượt hạn mức (tổng / giáo trình / tham khảo) cho mọi hình thức mượn.
     */
    public function assertBorrowWithinPolicyLimits(
        LibraryCard $card,
        int $newTextbook,
        int $newReference,
        int $newTotal
    ): void {
        $current = $this->currentOutstandingBorrowCounts($card);
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
        if ($bookDeltas === []) {
            return;
        }

        $books = Book::query()
            ->whereIn('id', array_keys($bookDeltas))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($bookDeltas as $bookId => $delta) {
            if ($delta <= 0) {
                throw new RuntimeException('Số lượng trừ kho phải lớn hơn 0.');
            }
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

        $this->deductBookCopiesByDeltas($bookDeltas);
    }

    /**
     * Cộng tồn kho theo delta từng đầu sách (khi trả/hủy phiếu).
     *
     * @param  array<int, int>  $bookDeltas
     */
    public function restockBooksByDeltas(array $bookDeltas): void
    {
        // Cộng tồn trực tiếp theo quantity hiện hành.
        // Khi bổ sung ledger kho, có thể mở rộng bằng cách ghi movement tại cùng transaction này.
        if ($bookDeltas === []) {
            return;
        }

        $books = Book::query()
            ->whereIn('id', array_keys($bookDeltas))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($bookDeltas as $bookId => $delta) {
            if ($delta <= 0) {
                throw new RuntimeException('Số lượng cộng kho phải lớn hơn 0.');
            }
            $book = $books->get($bookId);
            if (! $book instanceof Book) {
                throw new RuntimeException('Sách không tồn tại trong hệ thống');
            }
            $book->quantity += $delta;
            $book->save();
        }

        $this->restockBookCopiesByDeltas($bookDeltas);
    }

    /**
     * Trừ bản sách khả dụng theo từng đầu sách: chuyển AVAILABLE -> BORROWED.
     *
     * @param  array<int, int>  $bookDeltas
     */
    private function deductBookCopiesByDeltas(array $bookDeltas): void
    {
        foreach ($bookDeltas as $bookId => $delta) {
            if ($delta <= 0) {
                continue;
            }

            $this->ensureBookCopiesForBorrow((int) $bookId, $delta);

            $copies = BookCopy::query()
                ->where('book_id', $bookId)
                ->where('status', BookStatus::AVAILABLE)
                ->whereIn('physical_condition', BookPhysicalCondition::borrowableValues())
                ->lockForUpdate()
                ->orderBy('id')
                ->limit($delta)
                ->get(['id']);

            if ($copies->count() < $delta) {
                $book = Book::query()->whereKey($bookId)->first(['id', 'title']);
                $available = BookCopy::query()
                    ->where('book_id', $bookId)
                    ->where('status', BookStatus::AVAILABLE)
                    ->whereIn('physical_condition', BookPhysicalCondition::borrowableValues())
                    ->count();
                $title = $book?->title ?? 'đầu sách #'.$bookId;
                throw new RuntimeException(sprintf(
                    'Sách "%s" không đủ bản sẵn sàng cho mượn (cần %d, còn %d bản khả dụng). Kiểm tra bản in trong kho hoặc tình trạng vật lý.',
                    $title,
                    $delta,
                    $available
                ));
            }

            BookCopy::query()
                ->whereIn('id', $copies->pluck('id')->all())
                ->update(['status' => BookStatus::BORROWED->value]);
        }

        $this->storageQuantitySyncService->syncAll();
    }

    /**
     * Đầu sách cũ có thể chỉ có books.quantity mà chưa có book_copies — tạo bản in khớp tồn trước khi gán mượn.
     * Gọi sau deductBooksByDeltas: quantity đã trừ delta, target = quantity hiện tại + delta.
     */
    private function ensureBookCopiesForBorrow(int $bookId, int $borrowDelta): void
    {
        $book = Book::query()->whereKey($bookId)->lockForUpdate()->first();
        if (! $book instanceof Book) {
            return;
        }

        $totalCopies = (int) BookCopy::query()->where('book_id', $bookId)->count();
        $targetTotal = max(0, (int) $book->quantity) + $borrowDelta;
        if ($totalCopies >= $targetTotal) {
            return;
        }

        $toCreate = $targetTotal - $totalCopies;
        for ($seq = 1; $seq <= $toCreate; $seq++) {
            $barcode = $this->generateProvisionalBookCopyBarcode($bookId, $totalCopies + $seq);
            BookCopy::query()->create([
                'book_id' => $bookId,
                'barcode' => $barcode,
                'status' => BookStatus::AVAILABLE,
                'physical_condition' => BookPhysicalCondition::GOOD,
                'warehouse_id' => $book->warehouse_id,
            ]);
        }
    }

    private function generateProvisionalBookCopyBarcode(int $bookId, int $sequence): string
    {
        $attempts = 0;
        do {
            $attempts++;
            $code = sprintf('BC-%d-%s-%04d', $bookId, now()->format('ymdHis'), $sequence + $attempts);
            $exists = BookCopy::query()->where('barcode', $code)->exists();
        } while ($exists && $attempts < 10);

        if ($exists) {
            $code = sprintf('BC-%d-%s', $bookId, strtoupper(Str::random(8)));
        }

        return $code;
    }

    /**
     * Hoàn bản sách theo từng đầu sách: chuyển BORROWED -> AVAILABLE.
     *
     * @param  array<int, int>  $bookDeltas
     */
    private function restockBookCopiesByDeltas(array $bookDeltas): void
    {
        foreach ($bookDeltas as $bookId => $delta) {
            if ($delta <= 0) {
                continue;
            }
            $copies = BookCopy::query()
                ->where('book_id', $bookId)
                ->where('status', BookStatus::BORROWED)
                ->lockForUpdate()
                ->orderBy('id')
                ->limit($delta)
                ->get(['id']);

            if ($copies->count() < $delta) {
                throw new RuntimeException('Không đủ bản sách đang mượn để hoàn kho');
            }

            BookCopy::query()
                ->whereIn('id', $copies->pluck('id')->all())
                ->update(['status' => BookStatus::AVAILABLE->value]);
        }

        $this->storageQuantitySyncService->syncAll();
    }

    /**
     * Tính tiền phạt cho một dòng mượn:
     * - Phạt trễ hạn theo ngày.
     * - Hư hỏng: giá sách × (% mức hư / 100).
     * - Mất sách: (giá × hệ số + phí xử lý) — tương đương 100%.
     */
    private function calculateFineAmount(
        Loan $loan,
        LoanItem $item,
        LoanPolicy $policy,
        ?string $conditionOnReturn,
        ?int $damageSeverityPercent = null
    ): float {
        $overdueFine = $this->calculateOverdueFine($loan, $item, $policy);
        $conditionFine = $this->calculateConditionFine(
            $item,
            $policy,
            $conditionOnReturn,
            $damageSeverityPercent
        );

        return round($overdueFine + $conditionFine, 2);
    }

    private function calculateOverdueFine(Loan $loan, LoanItem $item, LoanPolicy $policy): float
    {
        $overdueFinePerDay = max(0, (float) ($policy->overdue_fine_per_day ?? 0));
        $overdueDays = 0;
        if ($loan->due_date !== null && $loan->return_date !== null && $loan->return_date->gt($loan->due_date)) {
            $overdueDays = (int) $loan->due_date->diffInDays($loan->return_date);
        }

        $bookPrice = $this->resolveBookPriceForLoanItem($item);
        $lateMode = $this->librarySettings->getLateReturnFineMode();
        if ($lateMode === LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY) {
            $pct = $this->librarySettings->getLateReturnFinePercentOfBook() / 100.0;

            return $overdueDays * ($bookPrice * $pct) * (int) $item->quantity;
        }

        return $overdueDays * $overdueFinePerDay * (int) $item->quantity;
    }

    private function calculateConditionFine(
        LoanItem $item,
        LoanPolicy $policy,
        ?string $conditionOnReturn,
        ?int $damageSeverityPercent
    ): float {
        $bookPrice = $this->resolveBookPriceForLoanItem($item);
        $quantity = (int) $item->quantity;
        $params = is_array($policy->params) ? $policy->params : [];
        $lossMultiplier = max(1, (float) ($params['loss_fine_multiplier'] ?? 2));
        $processingFee = max(0, (float) ($params['replacement_processing_fee'] ?? 10000));

        if ($conditionOnReturn === LoanItemCondition::DAMAGED->value) {
            $severity = min(100, max(1, $damageSeverityPercent ?? 100));

            return $bookPrice * ($severity / 100) * $quantity;
        }
        if ($conditionOnReturn === LoanItemCondition::LOST->value) {
            return (($bookPrice * $lossMultiplier) + $processingFee) * $quantity;
        }

        return 0.0;
    }
}
