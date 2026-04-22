<?php

namespace App\Services;

use App\Enums\ResourceType;
use App\Exports\BooksWorkbookExport;
use App\Helpers\FileHelpers;
use App\Imports\BookImport;
use App\Enums\BookPhysicalCondition;
use App\Enums\BookStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\ClassificationDetail;
use App\Models\Publisher;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
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
            if (empty($bookData['registration_number'])) {
                $bookData['registration_number'] = $this->generateRegistrationNumber($warehouse);
            }
            if (! empty($bookData['classification_detail_id']) && empty($bookData['book_code'])) {
                $classificationDetail = ClassificationDetail::findOrFail($bookData['classification_detail_id']);
                $bookData['book_code'] = $this->generateBookCode($classificationDetail, $warehouse);
            }
            $book = Book::create($bookData);
            $this->syncContributors($book, $authorsInput, $publisherInput);
            if ($syncThesis) {
                $this->syncThesisMetadata($book, $thesisMeta);
            }

            return $book->fresh([
                'classification:id,code,name',
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
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

            $book->update($data);
            $this->syncContributors($book, $authorsInput, $publisherInput);
            if ($syncThesis) {
                $this->syncThesisMetadata($book, $thesisMeta);
            }

            return $book->fresh([
                'classification:id,code,name',
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
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
            'classificationDetail:id,code,name,classification_id',
            'warehouse:id,code,name',
            'authors:id,name',
            'publishers:id,name',
            'digitalAssets',
            'thesisMetadata',
        ]);
    }

    /**
     * @param  list<string>|null  $keywordColumns
     */
    public function index(
        ?string $keyword,
        ?string $resourceType,
        int $perPage = self::PER_PAGE,
        ?array $keywordColumns = null
    ): LengthAwarePaginator {
        $query = $this->baseBookListQuery();
        $this->applyResourceTypeFilter($query, $resourceType);
        $this->applyKeywordFilterToBookQuery($query, $keyword, $keywordColumns);

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
        ?int $classificationDetailId,
        ?string $stock,
        string $sort = 'newest'
    ): LengthAwarePaginator {
        $query = $this->baseBookListQuery();
        $this->applyResourceTypeFilter($query, $resourceType);
        $this->applyKeywordFilterToBookQuery($query, $keyword, $keywordColumns);

        if ($classificationId !== null) {
            $query->where('classification_id', $classificationId);
        }
        if ($classificationDetailId !== null) {
            $query->where('classification_detail_id', $classificationDetailId);
        }
        if ($stock === 'in_stock') {
            $query->where('quantity', '>', 0);
        } elseif ($stock === 'out_of_stock') {
            $query->where(function ($q) {
                $q->where('quantity', '<=', 0)->orWhereNull('quantity');
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Thống kê bản in cho trang chi tiết tra cứu (book_copies; fallback quantity nếu chưa có bản).
     *
     * @return array{total: int, available: int, borrowed: int}
     */
    public function readerCopyStats(Book $book): array
    {
        $total = (int) $book->copies()->count();
        if ($total === 0) {
            $q = max(0, (int) ($book->quantity ?? 0));

            return [
                'total' => $q,
                'available' => $book->is_available ? $q : 0,
                'borrowed' => $book->is_available ? 0 : $q,
            ];
        }

        $available = (int) $book->copies()
            ->where('status', BookStatus::AVAILABLE)
            ->whereIn('physical_condition', BookPhysicalCondition::borrowableValues())
            ->count();
        $borrowed = (int) $book->copies()->where('status', BookStatus::BORROWED)->count();

        return [
            'total' => $total,
            'available' => $available,
            'borrowed' => $borrowed,
        ];
    }

    /**
     * @return Builder<Book>
     */
    private function baseBookListQuery(): Builder
    {
        return Book::query()
            ->with([
                'classification:id,code,name',
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
            ]);
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
                $q->orWhereHas('classificationDetail', function ($sub) use ($keyword) {
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
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
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
            ->with(['classification:id,name', 'classificationDetail:id,name', 'warehouse:id,name'])
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
     * Sinh số đăng ký cá biệt theo từng kho.
     * Ví dụ: TVTT-0001
     */
    private function generateRegistrationNumber(Warehouse $warehouse): string
    {
        $lastRegistration = Book::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereNotNull('registration_number')
            ->orderByDesc('id')
            ->value('registration_number');
        $nextNumber = 1;
        if ($lastRegistration && preg_match('/(\d+)$/', $lastRegistration, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s-%04d', $warehouse->code, $nextNumber);
    }

    /**
     * Sinh mã đầu sách (book_code) theo quy tắc:
     * <Mã phân loại rút gọn> - <Mã kho> - <Số thứ tự 4 chữ số>
     *
     * Ví dụ: 6242-TVTT-0015
     */
    private function generateBookCode(ClassificationDetail $classificationDetail, Warehouse $warehouse): string
    {
        $shortClassificationCode = str_replace('.', '', (string) $classificationDetail->code);

        $lastBookCode = Book::query()
            ->where('classification_detail_id', $classificationDetail->id)
            ->where('warehouse_id', $warehouse->id)
            ->whereNotNull('book_code')
            ->orderByDesc('id')
            ->value('book_code');
        $nextOrder = 1;
        if ($lastBookCode && preg_match('/-(\d{4})$/', $lastBookCode, $matches)) {
            $nextOrder = (int) $matches[1] + 1;
        }
        $orderPart = str_pad((string) $nextOrder, 4, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%s', $shortClassificationCode, $warehouse->code, $orderPart);
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
