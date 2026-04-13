<?php

namespace App\Services;

use App\Enums\AccessMode;
use App\Enums\LibraryCardStatus;
use App\Enums\ResourceType;
use App\Helpers\LoanHelper;
use App\Models\Book;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LoanService
{
    public function __construct(
        private LoanPoliciesService $loanPoliciesService,
        private LoanHelper $loanHelper
    ) {}

    /**
     * Phiếu còn mở trên quầy: đang mượn hoặc quá hạn (chưa trả).
     */
    private function assertLoanOpenForOfficeOps(Loan $loan): void
    {
        $ok = in_array($loan->status, [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE], true);
        if (! $ok) {
            throw new RuntimeException('Phiếu mượn không đang ở trạng thái mượn');
        }
    }

    /**
     * Tạo phiếu mượn.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Loan
    {
        $loanType = (string) ($data['loan_type'] ?? Loan::TYPE_HOME);

        return match ($loanType) {
            Loan::TYPE_ONSITE => $this->createOnsiteLoan($data),
            default => $this->createHomeBorrow($data),
        };
    }

    /**
     * Tạo phiếu mượn về nhà.
     * - Kiểm tra thẻ có được phép mượn (không bị khóa, không nợ quá hạn > 30 ngày).
     * - Kiểm tra quyền policy cho mượn về nhà.
     * - Kiểm tra hạn mức theo loại sách/tổng (cộng cả phiếu tại chỗ đang mở).
     * - Lưu phiếu + trừ tồn kho.
     *
     * @param  array<string, mixed>  $data
     */
    public function createHomeBorrow(array $data): Loan
    {
        return DB::transaction(function () use ($data): Loan {
            $card = $this->loadBorrowableCard((int) $data['library_card_id']);
            $permissions = $this->loanPoliciesService->getBorrowPermissionsForHolderType((string) $card->holder_type);
            if (! $permissions['allow_home']) {
                throw new RuntimeException('Loại thẻ này không được mượn tài liệu về nhà');
            }

            [$entries, $sumQuantityTextBook, $sumQuantityReference, $sumQuantityAll] = $this->buildLoanEntries(
                $data,
                Loan::TYPE_HOME
            );

            $this->loanHelper->assertBorrowWithinPolicyLimits(
                $card,
                $sumQuantityTextBook,
                $sumQuantityReference,
                $sumQuantityAll
            );

            return $this->persistLoanAndItems($data, Loan::TYPE_HOME, $entries);
        });
    }

    /**
     * Tạo phiếu đọc/mượn tại chỗ (cho mọi tài liệu vật lý, trừ tài liệu số).
     * - Kiểm tra thẻ hợp lệ để mượn.
     * - Kiểm tra quyền policy cho mượn tại chỗ.
     * - Kiểm tra hạn mức.
     * - Lưu phiếu + trừ tồn kho.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOnsiteLoan(array $data): Loan
    {
        return DB::transaction(function () use ($data): Loan {
            $card = $this->loadBorrowableCard((int) $data['library_card_id']);
            $permissions = $this->loanPoliciesService->getBorrowPermissionsForHolderType((string) $card->holder_type);
            if (! $permissions['allow_onsite']) {
                throw new RuntimeException('Loại thẻ này không được đọc/mượn tại chỗ');
            }

            [$entries, $sumQuantityTextBook, $sumQuantityReference, $sumQuantityAll] = $this->buildLoanEntries(
                $data,
                Loan::TYPE_ONSITE
            );

            $this->loanHelper->assertBorrowWithinPolicyLimits(
                $card,
                $sumQuantityTextBook,
                $sumQuantityReference,
                $sumQuantityAll
            );

            return $this->persistLoanAndItems($data, Loan::TYPE_ONSITE, $entries);
        });
    }

    /**
     * Ghi dữ liệu phiếu mượn và các dòng chi tiết vào DB, sau đó trừ tồn kho theo tổng từng đầu sách.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array{book:Book,book_id:int,quantity:int,condition_on_loan:?string}>  $entries
     */
    private function persistLoanAndItems(array $data, string $loanType, array $entries): Loan
    {
        $loan = Loan::create([
            'loan_code' => $this->generateLoanCode(),
            'library_card_id' => $data['library_card_id'],
            'loan_type' => $loanType,
            'loan_date' => $data['loan_date'],
            'due_date' => $data['due_date'],
            'status' => $data['status'] ?? Loan::STATUS_BORROWED,
        ]);

        $deductionsByBook = [];
        foreach ($entries as $entry) {
            LoanItem::create([
                'loan_id' => $loan->id,
                'book_id' => $entry['book_id'],
                'quantity' => $entry['quantity'],
                'condition_on_loan' => $entry['condition_on_loan'],
                'notes' => $data['notes'] ?? null,
            ]);

            $deductionsByBook[$entry['book_id']] = ($deductionsByBook[$entry['book_id']] ?? 0) + $entry['quantity'];
        }
        $this->loanHelper->deductBooksByDeltas($deductionsByBook);

        return $loan->fresh('items');
    }

    /**
     * Chuẩn hóa dữ liệu request thành danh sách entry mượn và thống kê số lượng theo nhóm.
     * Đồng thời khóa bản ghi sách để chống race-condition khi mượn đồng thời.
     *
     * @param  array<string, mixed>  $data
     * @return array{0:list<array{book:Book,book_id:int,quantity:int,condition_on_loan:?string}>,1:int,2:int,3:int}
     */
    private function buildLoanEntries(array $data, string $loanType): array
    {
        $rawBookIds = array_map(static fn ($v) => (int) $v, (array) ($data['book_ids'] ?? []));
        $bookIds = array_values(array_unique($rawBookIds));
        $books = Book::query()->whereIn('id', $bookIds)->lockForUpdate()->get()->keyBy('id');

        if ($books->count() !== count($bookIds)) {
            throw new RuntimeException('Có sách không tồn tại trong hệ thống');
        }

        $entries = [];
        $requestedByBook = [];
        foreach ($rawBookIds as $index => $bookId) {
            $book = $books->get($bookId);
            if (! $book instanceof Book) {
                throw new RuntimeException('Có sách không tồn tại trong hệ thống');
            }

            $quantity = (int) (($data['quantity'][$index] ?? $data['quantity'][$bookId] ?? 1));
            if ($quantity < 1) {
                throw new RuntimeException('Số lượng mượn phải lớn hơn 0');
            }

            $entries[] = [
                'book' => $book,
                'book_id' => $bookId,
                'quantity' => $quantity,
                'condition_on_loan' => $this->loanHelper->normalizeCondition($data['condition_on_loan'][$index] ?? null),
            ];
            $requestedByBook[$bookId] = ($requestedByBook[$bookId] ?? 0) + $quantity;
        }

        foreach ($requestedByBook as $bookId => $requested) {
            $book = $books->get($bookId);
            if (! $book instanceof Book || $book->quantity < $requested) {
                throw new RuntimeException('Số lượng sách không đủ');
            }
        }

        $sumTextbook = 0;
        $sumReference = 0;
        $sumAll = 0;
        foreach ($entries as $entry) {
            $book = $entry['book'];
            $resourceType = $book->resource_type instanceof ResourceType
                ? $book->resource_type
                : ResourceType::tryFrom((string) $book->resource_type);
            $accessMode = $book->access_mode instanceof AccessMode
                ? $book->access_mode
                : AccessMode::tryFrom((string) $book->access_mode);

            if ($resourceType === ResourceType::DIGITAL || $accessMode === AccessMode::OnlineOnly) {
                throw new RuntimeException(sprintf(
                    'Tài liệu "%s" là tài liệu số, không áp dụng cho phiếu mượn bản in',
                    (string) $book->title
                ));
            }
            if ($loanType === Loan::TYPE_HOME && ! in_array($resourceType, [ResourceType::TEXTBOOK, ResourceType::REFERENCE], true)) {
                throw new RuntimeException(sprintf(
                    'Tài liệu "%s" chỉ được đọc/mượn tại chỗ, không áp dụng mượn về nhà',
                    (string) $book->title
                ));
            }

            if ($resourceType === ResourceType::TEXTBOOK) {
                $sumTextbook += $entry['quantity'];
            } elseif ($resourceType === ResourceType::REFERENCE) {
                $sumReference += $entry['quantity'];
            }
            $sumAll += $entry['quantity'];
        }

        return [$entries, $sumTextbook, $sumReference, $sumAll];
    }

    /**
     * Cập nhật phiếu mượn đang mở (hiện chỉ cho phép đổi hạn trả).
     * Trạng thái và ngày trả bắt buộc đi qua luồng trả sách riêng.
     */
    public function update(array $data, Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan, $data): Loan {
            $lockedLoan = Loan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertLoanOpenForOfficeOps($lockedLoan);

            if (array_key_exists('status', $data) || array_key_exists('return_date', $data)) {
                throw new RuntimeException('Không được cập nhật trạng thái/ngày trả ở update. Dùng processReturnBook().');
            }

            $payload = [];
            if (array_key_exists('due_date', $data)) {
                $payload['due_date'] = $data['due_date'];
            }

            if ($payload !== []) {
                $lockedLoan->update($payload);
            }

            return $lockedLoan->fresh();
        });
    }

    /**
     * Xóa mềm phiếu mượn.
     * - Đang mượn / quá hạn: hoàn tồn kho rồi đánh dấu xóa mềm.
     * - Đã trả: chỉ xóa mềm bản ghi (sách đã nhập kho lúc trả, không cộng tồn lần hai).
     */
    public function destroy(Loan $loan): void
    {
        DB::transaction(function () use ($loan): void {
            $lockedLoan = Loan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status === Loan::STATUS_RETURNED) {
                $lockedLoan->delete();

                return;
            }

            $this->assertLoanOpenForOfficeOps($lockedLoan);
            $items = $lockedLoan->items()->lockForUpdate()->get();
            $bookDeltas = $this->loanHelper->aggregateBookDeltas($items);
            $this->loanHelper->restockBooksByDeltas($bookDeltas);

            $lockedLoan->delete();
        });
    }

    /**
     * Xử lý trả sách:
     * - Chuyển trạng thái phiếu sang đã trả.
     * - Tính tiền phạt từng dòng (quá hạn/hư/mất).
     * - Cộng tồn kho trở lại.
     */
    public function processReturnBook(array $data, Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan, $data): Loan {
            $lockedLoan = Loan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();
            $card = LibraryCard::query()
                ->whereKey((int) $lockedLoan->library_card_id)
                ->lockForUpdate()
                ->firstOrFail();
            $policy = $this->loanHelper->resolvePolicyForCard($card);

            $this->assertLoanOpenForOfficeOps($lockedLoan);

            $lockedLoan->update([
                'status' => Loan::STATUS_RETURNED,
                'return_date' => $data['return_date'],
            ]);

            $items = $lockedLoan->items()->with('book:id,price')->lockForUpdate()->get();
            $bookDeltas = $this->loanHelper->aggregateBookDeltas($items);
            $returnsByItemId = is_array($data['returns'] ?? null) ? $data['returns'] : null;

            foreach ($items as $item) {
                $linePayload = $returnsByItemId[$item->id] ?? null;
                $item->update($this->loanHelper->buildReturnItemPayload(
                    $data,
                    is_array($linePayload) ? $linePayload : null,
                    $lockedLoan,
                    $item,
                    $policy
                ));
            }

            $this->loanHelper->restockBooksByDeltas($bookDeltas);

            return $lockedLoan->fresh('items');
        });
    }

    /**
     * Xóa mềm nhiều phiếu (đang mượn / quá hạn / đã trả). Một phiếu lỗi sẽ rollback cả lô.
     *
     * @param  list<int>  $ids
     */
    public function bulkDestroy(array $ids): void
    {
        $unique = array_values(array_unique(array_map('intval', $ids)));

        DB::transaction(function () use ($unique): void {
            foreach ($unique as $id) {
                $loan = Loan::query()->whereKey($id)->firstOrFail();
                $this->destroy($loan);
            }
        });
    }

    /**
     * Trả sách hàng loạt: cùng ngày trả và tình trạng mặc định cho mọi dòng (phạt tính theo chính sách).
     *
     * @param  list<int>  $loanIds
     */
    public function bulkProcessReturnBooks(array $loanIds, string $returnDate, string $defaultCondition = 'tot'): void
    {
        $unique = array_values(array_unique(array_map('intval', $loanIds)));
        $payload = [
            'return_date' => $returnDate,
            'condition_on_return' => $defaultCondition,
        ];

        DB::transaction(function () use ($unique, $payload): void {
            foreach ($unique as $id) {
                $loan = Loan::query()->whereKey($id)->firstOrFail();
                $this->processReturnBook($payload, $loan);
            }
        });
    }

    /**
     * Danh sách phiếu đã xóa mềm (thùng rác).
     */
    public function trash(int $perPage = 20): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return Loan::onlyTrashed()
            ->with(['libraryCard:id,card_number,full_name', 'createdBy:id,name'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Khôi phục phiếu đã xóa mềm. Phiếu đang mượn / quá hạn: trừ tồn kho lại (đã được cộng khi xóa mềm).
     */
    public function restore(int $id): ?Loan
    {
        return DB::transaction(function () use ($id): ?Loan {
            $loan = Loan::onlyTrashed()
                ->with(['items'])
                ->whereKey($id)
                ->lockForUpdate()
                ->first();
            if (! $loan instanceof Loan) {
                return null;
            }

            if (in_array($loan->status, [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE], true)) {
                $bookDeltas = $this->loanHelper->aggregateBookDeltas($loan->items);
                $this->loanHelper->deductBooksByDeltas($bookDeltas);
            }

            $loan->restore();

            return $loan->fresh(['libraryCard:id,card_number,full_name', 'createdBy:id,name', 'items.book:id,title']);
        });
    }

    /**
     * @return int số phiếu đã khôi phục
     */
    public function restoreMany(array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
        if ($ids === []) {
            return 0;
        }

        $restored = 0;
        foreach ($ids as $id) {
            if ($this->restore($id) !== null) {
                $restored++;
            }
        }

        return $restored;
    }

    public function forceDelete(int $id): bool
    {
        $loan = Loan::onlyTrashed()->whereKey($id)->first();
        if (! $loan instanceof Loan) {
            return false;
        }
        $loan->forceDelete();

        return true;
    }

    /**
     * @return int số bản ghi đã xóa vĩnh viễn (kèm loan_items theo cascade)
     */
    public function forceDeleteMany(array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
        if ($ids === []) {
            return 0;
        }

        return (int) Loan::onlyTrashed()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * Tải thẻ (khóa hàng DB trong transaction) trước khi mượn.
     * Việc ghi trạng thái LOCKED do quá hạn nặng do lệnh {@see SyncLibraryCardOverdueLocksCommand} xử lý định kỳ.
     */
    private function loadBorrowableCard(int $libraryCardId): LibraryCard
    {
        $lockedCard = LibraryCard::query()
            ->whereKey($libraryCardId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($lockedCard->status === LibraryCardStatus::LOCKED) {
            throw new RuntimeException('Thẻ đang bị khóa, không thể mượn thêm');
        }

        return $lockedCard;
    }

    /**
     * Tạo mã phiếu mượn ngắn gọn, duy nhất theo ngày.
     * Ví dụ: L260408A1B2
     */
    private function generateLoanCode(): string
    {
        $prefix = 'L'.now()->format('ymd');
        $attempts = 0;

        do {
            $attempts++;
            $code = $prefix.strtoupper(Str::random(4));
            $exists = Loan::withTrashed()->where('loan_code', $code)->exists();
        } while ($exists && $attempts < 10);

        if ($exists) {
            throw new RuntimeException('Không thể tạo mã phiếu mượn duy nhất, vui lòng thử lại');
        }

        return $code;
    }
}
