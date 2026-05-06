<?php

namespace App\Services;

use App\Enums\LibraryCardStatus;
use App\Enums\LoanItemCondition;
use App\Helpers\LoanHelper;
use App\Models\Book;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanBorrowRequest;
use App\Models\LoanBorrowRequestItem;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LoanBorrowRequestService
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly LoanPoliciesService $loanPoliciesService,
        private readonly LoanHelper $loanHelper
    ) {}

    public function createForReader(User $user, array $data): LoanBorrowRequest
    {
        return DB::transaction(function () use ($user, $data): LoanBorrowRequest {
            $card = $this->loadActiveCardByUser($user);
            $loanType = (string) ($data['loan_type'] ?? Loan::TYPE_HOME);
            $this->assertLoanTypeAllowedForCard($card, $loanType);
            $requestEntries = $this->buildRequestEntries($data, $loanType);

            [$sumTextbook, $sumReference, $sumAll] = $this->sumByResourceType($requestEntries);
            [$pendingTextbook, $pendingReference, $pendingTotal] = $this->pendingBorrowRequestCounts($card);
            $this->loanHelper->assertBorrowWithinPolicyLimits(
                $card,
                $sumTextbook + $pendingTextbook,
                $sumReference + $pendingReference,
                $sumAll + $pendingTotal
            );
            $this->assertLockableStock($requestEntries);

            $request = LoanBorrowRequest::query()->create([
                'request_code' => $this->generateRequestCode(),
                'library_card_id' => (int) $card->id,
                'requested_by' => (int) $user->id,
                'loan_type' => $loanType,
                'requested_loan_date' => $data['requested_loan_date'] ?? null,
                'requested_due_date' => $data['requested_due_date'] ?? null,
                'status' => LoanBorrowRequest::STATUS_PENDING,
                'request_note' => $this->nullableTrim($data['request_note'] ?? null),
            ]);

            foreach ($requestEntries as $entry) {
                LoanBorrowRequestItem::query()->create([
                    'borrow_request_id' => $request->id,
                    'book_id' => $entry['book_id'],
                    'quantity' => $entry['quantity'],
                    'notes' => $this->nullableTrim($entry['notes'] ?? null),
                ]);
            }

            return $request->fresh([
                'libraryCard:id,card_number,full_name,holder_type,user_id',
                'items.book:id,title,book_code,resource_type,cabinet,warehouse_id,quantity',
                'items.book.warehouse:id,code,name',
            ]);
        });
    }

    public function readerList(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = LoanBorrowRequest::query()
            ->with([
                'libraryCard:id,card_number,full_name,holder_type,user_id',
                'items.book:id,title,book_code,resource_type,cabinet,warehouse_id,quantity',
                'items.book.warehouse:id,code,name',
                'reviewer:id,name',
                'approvedLoan:id,loan_code,status,loan_date,due_date',
            ])
            ->where('requested_by', (int) $user->id);

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function adminList(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = LoanBorrowRequest::query()
            ->with([
                'libraryCard:id,card_number,full_name,holder_type,user_id',
                'requester:id,name,code,email',
                'reviewer:id,name',
                'approvedLoan:id,loan_code,status',
                'items.book:id,title,book_code,resource_type,cabinet,warehouse_id,quantity',
                'items.book.warehouse:id,code,name',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        $kw = trim((string) ($filters['search'] ?? ''));
        if ($kw !== '') {
            $searchIn = array_values(array_filter((array) ($filters['search_in'] ?? []), static fn ($v): bool => is_string($v) && $v !== ''));
            if ($searchIn === []) {
                $searchIn = ['request_code', 'card', 'reader', 'book'];
            }
            $query->where(function ($q) use ($kw, $searchIn): void {
                $applied = false;
                if (in_array('request_code', $searchIn, true)) {
                    $q->where('request_code', 'like', "%{$kw}%");
                    $applied = true;
                }
                if (in_array('card', $searchIn, true)) {
                    $method = $applied ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('libraryCard', fn ($x) => $x->where('card_number', 'like', "%{$kw}%")->orWhere('full_name', 'like', "%{$kw}%"));
                    $applied = true;
                }
                if (in_array('reader', $searchIn, true)) {
                    $method = $applied ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('requester', fn ($x) => $x->where('name', 'like', "%{$kw}%")->orWhere('code', 'like', "%{$kw}%"));
                    $applied = true;
                }
                if (in_array('book', $searchIn, true)) {
                    $method = $applied ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('items.book', fn ($x) => $x->where('title', 'like', "%{$kw}%")->orWhere('book_code', 'like', "%{$kw}%"));
                }
            });
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function approve(LoanBorrowRequest $borrowRequest, User $reviewer, array $payload): LoanBorrowRequest
    {
        return DB::transaction(function () use ($borrowRequest, $reviewer, $payload): LoanBorrowRequest {
            $locked = LoanBorrowRequest::query()
                ->with(['libraryCard', 'items'])
                ->whereKey($borrowRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== LoanBorrowRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Yêu cầu này đã được xử lý trước đó.'],
                ]);
            }

            $loanDate = (string) ($payload['loan_date'] ?? now()->toDateString());
            $dueDate = (string) ($payload['due_date'] ?? $locked->requested_due_date?->toDateString() ?? now()->addDays(7)->toDateString());
            [$bookIds, $quantities, $conditions] = $this->resolveApprovedLoanEntries($locked, $payload);

            $loan = $this->loanService->create([
                'library_card_id' => (int) $locked->library_card_id,
                'loan_type' => (string) ($payload['loan_type'] ?? $locked->loan_type),
                'loan_date' => $loanDate,
                'due_date' => $dueDate,
                'status' => Loan::STATUS_BORROWED,
                'book_ids' => $bookIds,
                'quantity' => $quantities,
                'condition_on_loan' => $conditions,
                'notes' => $this->nullableTrim($payload['review_note'] ?? null) ?? $locked->request_note,
            ]);

            foreach ($locked->items as $idx => $item) {
                $existingIdx = array_search((int) $item->book_id, $bookIds, true);
                $item->update(['condition_on_loan' => $conditions[$existingIdx !== false ? $existingIdx : $idx] ?? LoanItemCondition::GOOD->value]);
            }

            $locked->update([
                'status' => LoanBorrowRequest::STATUS_APPROVED,
                'reviewed_by' => (int) $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $this->nullableTrim($payload['review_note'] ?? null),
                'approved_loan_id' => (int) $loan->id,
            ]);

            return $locked->fresh([
                'libraryCard:id,card_number,full_name,holder_type,user_id',
                'requester:id,name,code,email',
                'reviewer:id,name',
                'approvedLoan:id,loan_code,status,loan_date,due_date',
                'items.book:id,title,book_code,resource_type,cabinet,warehouse_id,quantity',
                'items.book.warehouse:id,code,name',
            ]);
        });
    }

    /**
     * @return array{0:list<int>,1:list<int>,2:list<string>}
     */
    private function resolveApprovedLoanEntries(LoanBorrowRequest $locked, array $payload): array
    {
        $overrideBookIds = array_values(array_map('intval', (array) ($payload['book_ids'] ?? [])));
        if ($overrideBookIds !== []) {
            $overrideQuantitiesRaw = array_values((array) ($payload['quantity'] ?? []));
            $overrideConditionsRaw = array_values((array) ($payload['condition_on_loan'] ?? []));

            if (count($overrideQuantitiesRaw) !== count($overrideBookIds)) {
                throw ValidationException::withMessages([
                    'quantity' => ['Số lượng phải tương ứng với từng đầu sách.'],
                ]);
            }

            $bookIds = [];
            $quantities = [];
            $conditions = [];
            foreach ($overrideBookIds as $idx => $bookId) {
                $qty = (int) ($overrideQuantitiesRaw[$idx] ?? 0);
                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'quantity' => ['Số lượng mượn phải lớn hơn 0.'],
                    ]);
                }
                $bookIds[] = $bookId;
                $quantities[] = $qty;
                $conditions[] = $this->normalizeCondition((string) ($overrideConditionsRaw[$idx] ?? LoanItemCondition::GOOD->value));
            }

            return [$bookIds, $quantities, $conditions];
        }

        $conditionMap = is_array($payload['condition_on_loan'] ?? null) ? $payload['condition_on_loan'] : [];
        $bookIds = [];
        $quantities = [];
        $conditions = [];
        foreach ($locked->items as $item) {
            $bookIds[] = (int) $item->book_id;
            $quantities[] = (int) $item->quantity;
            $conditions[] = $this->normalizeCondition((string) ($conditionMap[(int) $item->id] ?? $item->condition_on_loan ?? LoanItemCondition::GOOD->value));
        }

        return [$bookIds, $quantities, $conditions];
    }

    /**
     * Từ chối nhiều yêu cầu trong một giao dịch (chỉ các bản ghi đang chờ duyệt).
     *
     * @param  list<int>  $ids
     */
    public function bulkReject(User $reviewer, array $ids, ?string $reviewNote = null): int
    {
        return DB::transaction(function () use ($reviewer, $ids, $reviewNote): int {
            $ids = array_values(array_unique(array_map(static fn ($v): int => (int) $v, $ids)));
            sort($ids);

            $reviewNoteTrimmed = $this->nullableTrim($reviewNote);
            $count = 0;
            foreach ($ids as $id) {
                $locked = LoanBorrowRequest::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();
                if (! $locked instanceof LoanBorrowRequest) {
                    continue;
                }
                if ($locked->status !== LoanBorrowRequest::STATUS_PENDING) {
                    continue;
                }
                $locked->update([
                    'status' => LoanBorrowRequest::STATUS_REJECTED,
                    'reviewed_by' => (int) $reviewer->id,
                    'reviewed_at' => now(),
                    'review_note' => $reviewNoteTrimmed,
                ]);
                $count++;
            }

            return $count;
        });
    }

    public function reject(LoanBorrowRequest $borrowRequest, User $reviewer, ?string $reviewNote = null): LoanBorrowRequest
    {
        return DB::transaction(function () use ($borrowRequest, $reviewer, $reviewNote): LoanBorrowRequest {
            $locked = LoanBorrowRequest::query()
                ->whereKey($borrowRequest->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($locked->status !== LoanBorrowRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Yêu cầu này đã được xử lý trước đó.'],
                ]);
            }

            $locked->update([
                'status' => LoanBorrowRequest::STATUS_REJECTED,
                'reviewed_by' => (int) $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $this->nullableTrim($reviewNote),
            ]);

            return $locked->fresh([
                'libraryCard:id,card_number,full_name,holder_type,user_id',
                'requester:id,name,code,email',
                'reviewer:id,name',
                'items.book:id,title,book_code,resource_type,cabinet,warehouse_id,quantity',
                'items.book.warehouse:id,code,name',
            ]);
        });
    }

    private function assertLoanTypeAllowedForCard(LibraryCard $card, string $loanType): void
    {
        $permissions = $this->loanPoliciesService->getBorrowPermissionsForHolderType((string) $card->holder_type);
        if ($loanType === Loan::TYPE_HOME && ! $permissions['allow_home']) {
            throw new RuntimeException(
                'Theo quy định thư viện UTC, loại thẻ của bạn chỉ được đăng ký đọc tại chỗ, không mượn về nhà.'
            );
        }
        if ($loanType === Loan::TYPE_ONSITE && ! $permissions['allow_onsite']) {
            throw new RuntimeException(
                'Theo quy định thư viện, loại thẻ này không áp dụng hình thức đọc tại chỗ qua yêu cầu trực tuyến. Vui lòng liên hệ thủ thư.'
            );
        }
    }

    private function loadActiveCardByUser(User $user): LibraryCard
    {
        $card = LibraryCard::query()
            ->where('user_id', (int) $user->id)
            ->where('workflow_status', LibraryCard::WORKFLOW_ACTIVE)
            ->where('status', LibraryCardStatus::ACTIVE)
            ->lockForUpdate()
            ->first();

        if (! $card instanceof LibraryCard) {
            throw new RuntimeException('Bạn chưa có thẻ thư viện đang hoạt động để gửi yêu cầu mượn.');
        }

        return $card;
    }

    /** @return list<array{book_id:int,quantity:int,notes:?string,resource_type:string}> */
    private function buildRequestEntries(array $data, string $loanType): array
    {
        $rawBookIds = array_map(static fn ($v): int => (int) $v, (array) ($data['book_ids'] ?? []));
        $bookIds = array_values(array_unique($rawBookIds));
        if ($bookIds === []) {
            throw new RuntimeException('Vui lòng chọn ít nhất một đầu sách.');
        }

        $books = Book::query()->whereIn('id', $bookIds)->lockForUpdate()->get()->keyBy('id');
        if ($books->count() !== count($bookIds)) {
            throw new RuntimeException('Có sách không tồn tại trong hệ thống.');
        }

        $entries = [];
        foreach ($rawBookIds as $index => $bookId) {
            $book = $books->get($bookId);
            if (! $book instanceof Book) {
                throw new RuntimeException('Có sách không tồn tại trong hệ thống.');
            }
            $quantity = (int) (($data['quantity'][$index] ?? $data['quantity'][$bookId] ?? 1));
            if ($quantity < 1) {
                throw new RuntimeException('Số lượng mượn phải lớn hơn 0.');
            }
            $resourceType = (string) $book->resource_type->value;
            if ($loanType === Loan::TYPE_HOME && ! in_array($resourceType, ['textbook', 'reference'], true)) {
                throw new RuntimeException(sprintf('Tài liệu "%s" không hỗ trợ mượn về nhà.', (string) $book->title));
            }
            $entries[] = [
                'book_id' => (int) $bookId,
                'quantity' => $quantity,
                'notes' => $data['notes'][$index] ?? null,
                'resource_type' => $resourceType,
            ];
        }

        return $entries;
    }

    /** @param list<array{book_id:int,quantity:int,notes:?string,resource_type:string}> $entries */
    private function sumByResourceType(array $entries): array
    {
        $sumTextbook = 0;
        $sumReference = 0;
        $sumAll = 0;
        foreach ($entries as $entry) {
            $sumAll += (int) $entry['quantity'];
            if ($entry['resource_type'] === 'textbook') {
                $sumTextbook += (int) $entry['quantity'];
            }
            if ($entry['resource_type'] === 'reference') {
                $sumReference += (int) $entry['quantity'];
            }
        }

        return [$sumTextbook, $sumReference, $sumAll];
    }

    /**
     * Tính số lượng sách đang nằm trong các yêu cầu mượn chờ duyệt của cùng thẻ.
     *
     * @return array{0:int,1:int,2:int}
     */
    private function pendingBorrowRequestCounts(LibraryCard $card): array
    {
        $pendingByType = LoanBorrowRequestItem::query()
            ->join('loan_borrow_requests as req', 'loan_borrow_request_items.borrow_request_id', '=', 'req.id')
            ->join('books', 'loan_borrow_request_items.book_id', '=', 'books.id')
            ->where('req.library_card_id', (int) $card->id)
            ->where('req.status', LoanBorrowRequest::STATUS_PENDING)
            ->selectRaw('books.resource_type as resource_type, COALESCE(SUM(loan_borrow_request_items.quantity), 0) as total_quantity')
            ->groupBy('books.resource_type')
            ->pluck('total_quantity', 'resource_type');

        $textbook = (int) ($pendingByType['textbook'] ?? 0);
        $reference = (int) ($pendingByType['reference'] ?? 0);

        return [$textbook, $reference, $textbook + $reference];
    }

    /** @param list<array{book_id:int,quantity:int,notes:?string,resource_type:string}> $entries */
    private function assertLockableStock(array $entries): void
    {
        $byBook = [];
        foreach ($entries as $entry) {
            $bookId = (int) $entry['book_id'];
            $byBook[$bookId] = ($byBook[$bookId] ?? 0) + (int) $entry['quantity'];
        }

        foreach ($byBook as $bookId => $requestedQty) {
            $book = Book::query()->whereKey($bookId)->lockForUpdate()->first();
            if (! $book instanceof Book) {
                throw new RuntimeException('Có sách không tồn tại trong hệ thống.');
            }

            $onLoan = (int) LoanItem::query()
                ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
                ->where('loan_items.book_id', $bookId)
                ->where('loans.deleted', false)
                ->whereIn('loans.status', [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE])
                ->sum('loan_items.quantity');

            $reserved = (int) LoanBorrowRequestItem::query()
                ->join('loan_borrow_requests as req', 'loan_borrow_request_items.borrow_request_id', '=', 'req.id')
                ->where('loan_borrow_request_items.book_id', $bookId)
                ->where('req.status', LoanBorrowRequest::STATUS_PENDING)
                ->sum('loan_borrow_request_items.quantity');

            $available = (int) $book->quantity - $onLoan - $reserved;
            if ($available < $requestedQty) {
                throw new RuntimeException(sprintf(
                    'Sách "%s" không đủ số lượng giữ chỗ. Còn khả dụng: %d, yêu cầu: %d.',
                    (string) $book->title,
                    max(0, $available),
                    $requestedQty
                ));
            }
        }
    }

    private function normalizeCondition(string $value): string
    {
        $resolved = LoanItemCondition::tryFrom($value);
        if (! $resolved instanceof LoanItemCondition) {
            throw ValidationException::withMessages([
                'condition_on_loan' => ['Tình trạng sách không hợp lệ.'],
            ]);
        }

        return $resolved->value;
    }

    private function generateRequestCode(): string
    {
        $prefix = 'BR'.now()->format('ymd');
        $attempts = 0;
        do {
            $attempts++;
            $code = $prefix.strtoupper(Str::random(5));
            $exists = LoanBorrowRequest::query()->where('request_code', $code)->exists();
        } while ($exists && $attempts < 10);
        if ($exists) {
            throw new RuntimeException('Không thể tạo mã yêu cầu mượn duy nhất, vui lòng thử lại.');
        }

        return $code;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }
}
