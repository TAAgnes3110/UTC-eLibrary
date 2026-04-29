<?php

namespace App\Services;

use App\Enums\BookPhysicalCondition;
use App\Enums\BookStatus;
use App\Enums\ResourceType;
use App\Exports\BooksWorkbookExport;
use App\Helpers\FileHelpers;
use App\Imports\BookImport;
use App\Models\Author;
use App\Models\Book;
use App\Models\Classification;
use App\Models\Loan;
use App\Models\LoanBorrowRequest;
use App\Models\LoanBorrowRequestItem;
use App\Models\LoanItem;
use App\Models\Publisher;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use App\Support\WarehouseBookIdentifiers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookService
{
    private const PER_PAGE = 50;

    public function create(array $data): Book
    {
        return DB::transaction(function () use ($data) {
            $bookData = $data;
            $authorsInput = $bookData['authors'] ?? null;
            $publisherInput = $bookData['publisher'] ?? null;
            $syncThesis = array_key_exists('thesis_metadata', $bookData);
            $thesisMeta = $bookData['thesis_metadata'] ?? null;
            unset($bookData['thesis_metadata'], $bookData['authors'], $bookData['publisher']);

            $warehouse = Warehouse::findOrFail($bookData['warehouse_id']);
            $this->ensureStorageLocationForBookData($bookData, null);
            if (empty($bookData['registration_number'])) {
                $bookData['registration_number'] = $this->generateRegistrationNumber($warehouse);
            }
            if (empty($bookData['book_code'])) {
                $bookData['book_code'] = $this->generateBookCode($warehouse);
            }
            $book = Book::create($bookData);
            $this->syncContributors($book, $authorsInput, $publisherInput);
            if ($syncThesis) {
                $this->syncThesisMetadata($book, $thesisMeta);
            }

            return $book->fresh([
                'classification:id,code,name',
                'warehouse:id,code,name',
                'representativeStoredCopy',
                'thesisMetadata',
            ]);
        });
    }

    public function update(Book $book, array $data): Book
    {
        return DB::transaction(function () use ($book, $data) {
            $authorsInput = $data['authors'] ?? null;
            $publisherInput = $data['publisher'] ?? null;
            unset(
                $data['id'],
                $data['created_at'],
                $data['updated_at'],
                $data['authors'],
                $data['publisher'],
            );
            if (array_key_exists('cover_image', $data) && empty($data['cover_image'])) {
                unset($data['cover_image']);
            }
            $syncThesis = array_key_exists('thesis_metadata', $data);
            $thesisMeta = $data['thesis_metadata'] ?? null;
            unset($data['thesis_metadata']);
            $this->ensureStorageLocationForBookData($data, $book);

            $book->update($data);
            $this->syncContributors($book, $authorsInput, $publisherInput);
            if ($syncThesis) {
                $this->syncThesisMetadata($book, $thesisMeta);
            }

            return $book->fresh([
                'classification:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
                'representativeStoredCopy',
                'thesisMetadata',
            ]);
        });
    }

    /**
     * Chi tiết sách cho API (eager load đồng nhất, tránh N+1).
     */
    public function getForApiDetail(Book $book): Book
    {
        return $book->load([
            'classification:id,code,name',
            'warehouse:id,code,name',
            'authors:id,name',
            'publishers:id,name',
            'representativeStoredCopy',
            'digitalAssets',
            'thesisMetadata',
            'loanItems' => fn ($q) => $q
                ->with([
                    'loan:id,loan_code,loan_date,due_date,return_date,status,library_card_id',
                    'loan.libraryCard:id,user_id,full_name,card_number',
                    'loan.libraryCard.user:id,name,email',
                ])
                ->latest('id')
                ->limit(20),
        ]);
    }

    /**
     * @param  list<string>|null  $keywordColumns
     */
    public function index(
        ?string $keyword,
        ?string $resourceType,
        int $perPage = self::PER_PAGE,
        ?array $keywordColumns = null,
        ?string $sort = null
    ): LengthAwarePaginator {
        $query = $this->baseBookListQuery();
        $this->applyResourceTypeFilter($query, $resourceType);
        $this->applyKeywordFilterToBookQuery($query, $keyword, $keywordColumns);
        $this->applySortToBookQuery($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Tra cứu công khai: lọc theo phân loại, tình trạng tồn (quantity), cùng logic từ khóa như admin.
     *
     * @param  list<string>|null  $keywordColumns
     */
    public function readerCatalog(
        ?string $keyword,
        ?string $resourceType,
        int $perPage,
        ?array $keywordColumns,
        ?int $classificationId,
        ?string $stock,
        string $sort = 'newest'
    ): LengthAwarePaginator {
        $query = $this->baseBookListQuery();
        $this->applyBorrowableAvailabilityProjection($query);
        $this->applyResourceTypeFilter($query, $resourceType);
        $this->applyKeywordFilterToBookQuery($query, $keyword, $keywordColumns);

        if ($classificationId !== null) {
            $query->where('classification_id', $classificationId);
        }
        if ($stock === 'in_stock') {
            $query->whereRaw('(COALESCE(books.quantity, 0) - COALESCE(on_loan_total_count, 0) - COALESCE(reserved_pending_count, 0)) > 0');
        } elseif ($stock === 'out_of_stock') {
            $query->whereRaw('(COALESCE(books.quantity, 0) - COALESCE(on_loan_total_count, 0) - COALESCE(reserved_pending_count, 0)) <= 0');
        }

        if ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  list<int>  $bookIds
     * @return Collection<int, Book>
     */
    public function readerBorrowPreview(array $bookIds)
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($bookIds, fn ($v) => is_numeric($v)))));
        if ($ids === []) {
            return collect();
        }

        $query = $this->baseBookListQuery();
        $this->applyBorrowableAvailabilityProjection($query);

        $books = $query->whereIn('books.id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $books->get($id))
            ->filter();
    }

    /**
     * Thống kê bản in cho trang chi tiết tra cứu (book_copies; fallback quantity nếu chưa có bản).
     *
     * @return array{total: int, available: int, borrowed: int}
     */
    public function readerCopyStats(Book $book): array
    {
        $reservedPending = (int) LoanBorrowRequestItem::query()
            ->join('loan_borrow_requests as req', 'loan_borrow_request_items.borrow_request_id', '=', 'req.id')
            ->where('loan_borrow_request_items.book_id', (int) $book->id)
            ->where('req.status', LoanBorrowRequest::STATUS_PENDING)
            ->sum('loan_borrow_request_items.quantity');

        $total = (int) $book->copies()->count();
        if ($total === 0) {
            $q = max(0, (int) ($book->quantity ?? 0));
            $borrowed = (int) LoanItem::query()
                ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
                ->where('loan_items.book_id', (int) $book->id)
                ->where('loans.deleted', false)
                ->whereIn('loans.status', [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE])
                ->sum('loan_items.quantity');
            $available = max(0, $q - $borrowed - $reservedPending);

            return [
                'total' => $q,
                'available' => $available,
                'borrowed' => min($q, $borrowed),
                'reserved_pending' => $reservedPending,
            ];
        }

        $available = (int) $book->copies()
            ->where('status', BookStatus::AVAILABLE)
            ->whereIn('physical_condition', BookPhysicalCondition::borrowableValues())
            ->count();
        $borrowed = (int) $book->copies()->where('status', BookStatus::BORROWED)->count();
        $effectiveAvailable = max(0, $available - $reservedPending);

        return [
            'total' => $total,
            'available' => $effectiveAvailable,
            'borrowed' => $borrowed,
            'reserved_pending' => $reservedPending,
        ];
    }

    /**
     * Bổ sung các cột tính khả dụng theo nghiệp vụ đặt mượn:
     * - on_loan_total_count: số bản đang mượn/chưa trả
     * - reserved_pending_count: số bản đang được giữ chỗ bởi yêu cầu mượn pending
     * - available_for_borrow: số bản còn có thể nhận yêu cầu mới
     *
     * @param  Builder<Book>  $query
     */
    private function applyBorrowableAvailabilityProjection(Builder $query): void
    {
        $query->selectRaw(
            '(SELECT COALESCE(SUM(li.quantity), 0)
                FROM loan_items li
                INNER JOIN loans l ON l.id = li.loan_id
                WHERE li.book_id = books.id
                  AND l.deleted = 0
                  AND l.status IN (?, ?)
            ) AS on_loan_total_count',
            [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE]
        );

        $query->selectRaw(
            '(SELECT COALESCE(SUM(bri.quantity), 0)
                FROM loan_borrow_request_items bri
                INNER JOIN loan_borrow_requests br ON br.id = bri.borrow_request_id
                WHERE bri.book_id = books.id
                  AND br.status = ?
            ) AS reserved_pending_count',
            [LoanBorrowRequest::STATUS_PENDING]
        );

        $query->selectRaw(
            '(COALESCE(books.quantity, 0)
                - (SELECT COALESCE(SUM(li2.quantity), 0)
                    FROM loan_items li2
                    INNER JOIN loans l2 ON l2.id = li2.loan_id
                    WHERE li2.book_id = books.id
                      AND l2.deleted = 0
                      AND l2.status IN (?, ?))
                - (SELECT COALESCE(SUM(bri2.quantity), 0)
                    FROM loan_borrow_request_items bri2
                    INNER JOIN loan_borrow_requests br2 ON br2.id = bri2.borrow_request_id
                    WHERE bri2.book_id = books.id
                      AND br2.status = ?)
            ) AS available_for_borrow',
            [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE, LoanBorrowRequest::STATUS_PENDING]
        );
    }

    /**
     * @return Builder<Book>
     */
    private function baseBookListQuery(): Builder
    {
        return Book::query()
            ->with([
                'classification:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
                'representativeStoredCopy',
            ])
            ->withCount([
                'availableCopies as available_copies_count',
                'copies as copies_count',
                'copies as borrowed_copies_count' => fn (Builder $q) => $q
                    ->where('status', BookStatus::BORROWED),
                'copies as lost_copies_count' => fn (Builder $q) => $q
                    ->where('status', BookStatus::LOST),
                'copies as warehouse_copies_count' => fn (Builder $q) => $q
                    ->where('status', BookStatus::AVAILABLE),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function applySortToBookQuery(Builder $query, ?string $sort): void
    {
        $sort = strtolower(trim((string) $sort));
        if ($sort === '') {
            return;
        }

        $query->reorder();
        if ($sort === 'oldest') {
            $query->orderBy('created_at')->orderBy('id');

            return;
        }
        if ($sort === 'az') {
            $query->orderBy('title')->orderBy('id');

            return;
        }
        if ($sort === 'za') {
            $query->orderByDesc('title')->orderByDesc('id');

            return;
        }

        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @param  list<string>|null  $keywordColumns
     */
    private function applyKeywordFilterToBookQuery(Builder $query, ?string $keyword, ?array $keywordColumns): void
    {
        if ($keyword === null || $keyword === '') {
            return;
        }

        $effectiveColumns = ! empty($keywordColumns)
            ? $keywordColumns
            : ['code', 'title', 'author', 'publisher', 'place', 'year', 'classification'];
        $query->where(function ($q) use ($keyword, $effectiveColumns) {
            $applied = false;
            if (in_array('title', $effectiveColumns, true)) {
                $q->where('title', 'like', "%{$keyword}%");
                $applied = true;
            }
            if (in_array('code', $effectiveColumns, true)) {
                $method = $applied ? 'orWhere' : 'where';
                $q->{$method}('registration_number', 'like', "%{$keyword}%")
                    ->orWhere('book_code', 'like', "%{$keyword}%");
                $applied = true;
            }
            if (in_array('year', $effectiveColumns, true)) {
                $method = $applied ? 'orWhere' : 'where';
                $q->{$method}('published_year', 'like', "%{$keyword}%");
                $applied = true;
            }
            if (in_array('author', $effectiveColumns, true)) {
                $method = $applied ? 'orWhereHas' : 'whereHas';
                $q->{$method}('authors', function ($sub) use ($keyword) {
                    $sub->where('name', 'like', "%{$keyword}%");
                });
                $applied = true;
            }
            if (in_array('publisher', $effectiveColumns, true)) {
                $method = $applied ? 'orWhereHas' : 'whereHas';
                $q->{$method}('publishers', function ($sub) use ($keyword) {
                    $sub->where('name', 'like', "%{$keyword}%");
                });
                $applied = true;
            }
            if (in_array('classification', $effectiveColumns, true)) {
                $method = $applied ? 'orWhereHas' : 'whereHas';
                $q->{$method}('classification', function ($sub) use ($keyword) {
                    $sub->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                });
                $applied = true;
            }
            if (in_array('place', $effectiveColumns, true)) {
                $method = $applied ? 'orWhere' : 'where';
                $q->{$method}('publisher_place', 'like', "%{$keyword}%");
                $applied = true;
            }
            if (! $applied) {
                $q->where('title', 'like', "%{$keyword}%");
            }
        });
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function syncThesisMetadata(Book $book, mixed $meta): void
    {
        if ($meta === null || (is_array($meta) && $meta === [])) {
            $book->thesisMetadata()->delete();

            return;
        }
        if (! is_array($meta)) {
            return;
        }
        if (empty($meta['work_type'])) {
            throw ValidationException::withMessages([
                'thesis_metadata.work_type' => [__('Thesis metadata requires work_type.')],
            ]);
        }

        $book->thesisMetadata()->updateOrCreate(
            ['book_id' => $book->id],
            [
                'work_type' => $meta['work_type'],
                'degree_program' => $meta['degree_program'] ?? null,
                'supervisor_name' => $meta['supervisor_name'] ?? null,
                'supervisor_user_id' => $meta['supervisor_user_id'] ?? null,
                'defense_year' => $meta['defense_year'] ?? null,
                'keywords' => $meta['keywords'] ?? null,
                'abstract_text' => $meta['abstract_text'] ?? null,
                'params' => $meta['params'] ?? null,
            ]
        );
    }

    private function syncContributors(Book $book, mixed $authorsInput, mixed $publisherInput): void
    {
        if ($authorsInput !== null) {
            $authorsRaw = is_array($authorsInput) ? implode(';', $authorsInput) : (string) $authorsInput;
            $authorNames = preg_split('/[;,]+/u', $authorsRaw) ?: [];
            $authorNames = array_values(array_filter(array_map(
                static fn ($name) => trim((string) $name),
                $authorNames
            )));
            if ($authorNames === []) {
                $authorNames = ['Khuyết danh'];
            }

            $authorSync = [];
            foreach ($authorNames as $idx => $name) {
                $author = Author::query()->firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'params' => []]
                );
                if ($author->name !== $name) {
                    $author->name = $name;
                    $author->save();
                }
                $authorSync[$author->id] = ['order' => $idx];
            }
            $book->authors()->sync($authorSync);
        }

        if ($publisherInput !== null) {
            $publishersRaw = is_array($publisherInput) ? implode(';', $publisherInput) : (string) $publisherInput;
            $publisherNames = preg_split('/[;,]+/u', $publishersRaw) ?: [];
            $publisherNames = array_values(array_filter(array_map(
                static fn ($name) => trim((string) $name),
                $publisherNames
            )));
            if ($publisherNames === []) {
                $publisherNames = ['Nhà xuất bản Giao thông Vận tải'];
            }

            $publisherSync = [];
            foreach ($publisherNames as $idx => $publisherName) {
                $publisher = Publisher::query()->firstOrCreate(
                    ['slug' => Str::slug($publisherName)],
                    ['name' => $publisherName, 'params' => []]
                );
                if ($publisher->name !== $publisherName) {
                    $publisher->name = $publisherName;
                    $publisher->save();
                }
                $publisherSync[$publisher->id] = ['order' => $idx];
            }
            $book->publishers()->sync($publisherSync);
        }
    }

    /**
     * Lọc theo resource_type; hỗ trợ nhiều giá trị cách nhau bởi dấu phẩy (vd: textbook,reference).
     *
     * @param  Builder<Book>  $query
     */
    private function applyResourceTypeFilter(Builder $query, ?string $resourceType): void
    {
        if ($resourceType === null || $resourceType === '') {
            return;
        }
        $allowed = ResourceType::values();
        $parts = array_unique(array_filter(array_map('trim', explode(',', $resourceType))));
        $parts = array_values(array_filter($parts, static fn ($p) => in_array($p, $allowed, true)));
        if ($parts === []) {
            return;
        }
        $includeNullAsReference = in_array('reference', $parts, true);
        if (count($parts) === 1) {
            if ($includeNullAsReference && $parts[0] === 'reference') {
                $query->where(function ($q) {
                    $q->where('resource_type', 'reference')
                        ->orWhereNull('resource_type')
                        ->orWhere('resource_type', '');
                });
            } else {
                $query->where('resource_type', $parts[0]);
            }
        } else {
            $query->where(function ($q) use ($parts, $includeNullAsReference) {
                $q->whereIn('resource_type', $parts);
                if ($includeNullAsReference) {
                    $q->orWhereNull('resource_type')->orWhere('resource_type', '');
                }
            });
        }
    }

    public function destroy(Book $book): void
    {
        $book->delete();
    }

    public function trash(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return Book::onlyTrashed()
            ->with([
                'classification:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
                'representativeStoredCopy',
            ])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function restore(int $id): ?Book
    {
        $book = Book::onlyTrashed()->find($id);
        if (! $book) {
            return null;
        }
        $book->restore();

        return $book;
    }

    /** @return int số bản ghi đã khôi phục */
    public function restoreMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }

        return (int) Book::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function forceDelete(int $id): bool
    {
        $book = Book::onlyTrashed()->find($id);
        if (! $book) {
            return false;
        }
        $book->forceDelete();

        return true;
    }

    /** @return int số bản ghi đã xóa vĩnh viễn */
    public function forceDeleteMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }

        return Book::onlyTrashed()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * @return array{cover_image: string}
     */
    public function updateCoverImage(Book $book, UploadedFile $file): array
    {
        $baseName = $book->book_code ?: (string) $book->id;
        $path = FileHelpers::updateModelImage($book, $file, 'books', 'cover_image', $baseName);

        return ['cover_image' => $path];
    }

    public function adminList(int $perPage = 20): array
    {
        $books = Book::query()
            ->with([
                'classification:id,name',
                'warehouse:id,name',
                'representativeStoredCopy',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'books' => $books,
        ];
    }

    public function exportBooks(?array $ids = null): StreamedResponse
    {
        return BooksWorkbookExport::stream($ids);
    }

    public function importBooks(UploadedFile $file): array
    {
        return BookImport::import($file);
    }

    /**
     * Gợi ý mã sách và số đăng ký cá biệt theo kho (mã kho + phần số, viết liền).
     *
     * @return array{book_code: ?string, registration_number: string}
     */
    public function previewIdentifiers(int $warehouseId): array
    {
        $warehouse = Warehouse::findOrFail($warehouseId);

        return [
            'book_code' => $this->generateBookCode($warehouse),
            'registration_number' => $this->generateRegistrationNumber($warehouse),
        ];
    }

    /**
     * Kho tài liệu số (KHO-SO, …) không gán vị trí lưu trữ vật lý.
     */
    public function isDigitalDocumentWarehouse(Warehouse $warehouse): bool
    {
        $code = strtolower(trim((string) $warehouse->code));
        if (str_contains($code, 'kho-so')) {
            return true;
        }
        $name = strtolower((string) $warehouse->name);

        return str_contains($name, 'tài liệu số') || str_contains($name, 'tai lieu so');
    }

    /**
     * @return array<int, array{cabinet:string,stored_count:int}>
     */
    public function suggestStorageCabinets(int $warehouseId): array
    {
        $warehouse = Warehouse::query()->find($warehouseId);
        if (! $warehouse || $this->isDigitalDocumentWarehouse($warehouse)) {
            return [];
        }

        return StorageCabinet::query()
            ->select(['id', 'name', 'current_quantity'])
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get()
            ->map(function (StorageCabinet $cabinet) {
                return [
                    'cabinet' => (string) ($cabinet->name ?? ''),
                    'stored_count' => max(0, (int) ($cabinet->current_quantity ?? 0)),
                ];
            })
            ->sortBy('stored_count')
            ->values()
            ->all();
    }

    /**
     * Sinh số đăng ký cá biệt theo từng kho (mã kho + 4 chữ số, không gạch nối), ví dụ TVTT0001.
     */
    private function generateRegistrationNumber(Warehouse $warehouse): string
    {
        return WarehouseBookIdentifiers::nextRegistrationNumber($warehouse);
    }

    /**
     * Sinh mã sách: mã kho + số thứ tự 4 chữ số, không gạch nối (ví dụ KHO-GT0001).
     */
    private function generateBookCode(Warehouse $warehouse): string
    {
        return WarehouseBookIdentifiers::nextBookCode($warehouse);
    }

    /**
     * Đồng bộ vị trí tủ theo kho + phân loại.
     * Nếu chưa có tủ phù hợp thì tự tạo mới.
     *
     * @param  array<string, mixed>  $bookData
     */
    private function ensureStorageLocationForBookData(array &$bookData, ?Book $existingBook): void
    {
        $warehouseId = isset($bookData['warehouse_id'])
            ? (int) $bookData['warehouse_id']
            : (int) ($existingBook?->warehouse_id ?? 0);
        $classificationId = isset($bookData['classification_id'])
            ? (int) $bookData['classification_id']
            : (int) ($existingBook?->classification_id ?? 0);

        if ($warehouseId <= 0 || $classificationId <= 0) {
            return;
        }

        $warehouse = Warehouse::findOrFail($warehouseId);
        if ($this->isDigitalDocumentWarehouse($warehouse)) {
            $bookData['cabinet'] = null;

            return;
        }

        $classification = Classification::findOrFail($classificationId);
        $cabinet = StorageCabinet::withTrashed()
            ->where('warehouse_id', $warehouse->id)
            ->where('classification_id', $classification->id)
            ->orderByDesc('id')
            ->first();
        if (! $cabinet) {
            $cabinetName = trim(sprintf(
                'Tủ %s%s',
                $classification->code ? "{$classification->code} - " : '',
                (string) $classification->name
            ));
            $cabinet = StorageCabinet::query()->create([
                'warehouse_id' => (int) $warehouse->id,
                'classification_id' => (int) $classification->id,
                'code' => $this->generateStorageCabinetCode($warehouse),
                'name' => mb_substr($cabinetName, 0, 160),
                'is_active' => true,
                'current_quantity' => 0,
                'params' => [],
            ]);
        } else {
            if ($cabinet->trashed()) {
                $cabinet->restore();
            }
            if (! $cabinet->is_active) {
                $cabinet->is_active = true;
                $cabinet->save();
            }
        }

        $bookData['cabinet'] = (string) $cabinet->name;
    }

    private function generateStorageCabinetCode(Warehouse $warehouse): string
    {
        $shortWarehouseCode = strtoupper(str_replace('KHO-', '', trim((string) $warehouse->code)));
        $prefix = 'TU-'.($shortWarehouseCode !== '' ? $shortWarehouseCode : 'WH').'-';

        $existingCodes = StorageCabinet::query()
            ->withTrashed()
            ->where('warehouse_id', $warehouse->id)
            ->pluck('code')
            ->filter()
            ->values();

        $max = 0;
        foreach ($existingCodes as $code) {
            $codeStr = (string) $code;
            if (! str_starts_with($codeStr, $prefix)) {
                continue;
            }
            $numberPart = substr($codeStr, strlen($prefix));
            if (! ctype_digit($numberPart)) {
                continue;
            }
            $max = max($max, (int) $numberPart);
        }

        return sprintf('%s%02d', $prefix, $max + 1);
    }

    /**
     * Bulk update book cover từ zip (file name = book.book_code).
     *
     * @param  list<int>|null  $onlyBookIds  Nếu có — chỉ cập nhật sách có id thuộc danh sách (tick chọn trên admin).
     * @return array{updated:int, skipped:int, selected_count?: int, selected_missing?: int}
     */
    public function bulkUpdateCoverFromZip(UploadedFile $zipFile, ?array $onlyBookIds = null): array
    {
        $tmpDir = FileHelpers::extractZipToTemp($zipFile, 'book-covers');
        $updated = 0;
        $skipped = 0;
        $updatedBookIds = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if (! $fileInfo->isFile()) {
                    continue;
                }
                if (FileHelpers::shouldSkipZipExtractedFile($fileInfo)) {
                    $skipped++;

                    continue;
                }
                $ext = strtolower($fileInfo->getExtension() ?: '');
                if (! in_array($ext, FileHelpers::IMAGE_EXTENSIONS, true)) {
                    $skipped++;

                    continue;
                }
                $code = trim($fileInfo->getBasename('.'.$ext));
                if ($code === '') {
                    $skipped++;

                    continue;
                }

                $book = Book::query()->where('book_code', $code)->first();
                if (! $book) {
                    $skipped++;

                    continue;
                }
                if ($onlyBookIds !== null && $onlyBookIds !== [] && ! in_array((int) $book->id, $onlyBookIds, true)) {
                    $skipped++;

                    continue;
                }

                $uploaded = new UploadedFile(
                    $fileInfo->getPathname(),
                    $fileInfo->getBasename(),
                    FileHelpers::mimeForImageExtension($ext),
                    null,
                    true
                );
                try {
                    $baseName = $book->book_code ?: (string) $book->id;
                    FileHelpers::updateModelImage($book, $uploaded, 'books', 'cover_image', $baseName);
                    $updated++;
                    $updatedBookIds[] = (int) $book->id;
                } catch (\Throwable) {
                    $skipped++;
                }
            }
        } finally {
            FileHelpers::removeDirectory($tmpDir);
        }

        $out = ['updated' => $updated, 'skipped' => $skipped];
        if ($onlyBookIds !== null && $onlyBookIds !== []) {
            $uniqueUpdated = array_values(array_unique($updatedBookIds));
            $out['selected_count'] = count($onlyBookIds);
            $out['selected_missing'] = count(array_diff($onlyBookIds, $uniqueUpdated));
        }

        return $out;
    }
}
