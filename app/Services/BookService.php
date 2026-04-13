<?php

namespace App\Services;

use App\Enums\ResourceType;
use App\Exports\BooksWorkbookExport;
use App\Helpers\FileHelpers;
use App\Imports\BookImport;
use App\Models\Book;
use App\Models\ClassificationDetail;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookService
{
    private const PER_PAGE = 50;

    public function create(array $data): Book
    {
        return DB::transaction(function () use ($data) {
            $bookData = $data;
            $syncThesis = array_key_exists('thesis_metadata', $bookData);
            $thesisMeta = $bookData['thesis_metadata'] ?? null;
            unset($bookData['thesis_metadata']);

            $warehouse = Warehouse::findOrFail($bookData['warehouse_id']);
            if (empty($bookData['registration_number'])) {
                $bookData['registration_number'] = $this->generateRegistrationNumber($warehouse);
            }
            if (! empty($bookData['classification_detail_id']) && empty($bookData['book_code'])) {
                $classificationDetail = ClassificationDetail::findOrFail($bookData['classification_detail_id']);
                $bookData['book_code'] = $this->generateBookCode($classificationDetail, $warehouse);
            }
            $book = Book::create($bookData);
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
            unset(
                $data['id'],
                $data['created_at'],
                $data['updated_at'],
            );
            if (array_key_exists('cover_image', $data) && empty($data['cover_image'])) {
                unset($data['cover_image']);
            }
            $syncThesis = array_key_exists('thesis_metadata', $data);
            $thesisMeta = $data['thesis_metadata'] ?? null;
            unset($data['thesis_metadata']);

            $book->update($data);
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
        $query = Book::query()
            ->with([
                'classification:id,code,name',
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
            ]);
        $this->applyResourceTypeFilter($query, $resourceType);
        $query->when($keyword !== null && $keyword !== '', function ($q) use ($keyword, $keywordColumns) {
            $effectiveColumns = ! empty($keywordColumns)
                ? $keywordColumns
                : ['code', 'title', 'author', 'publisher', 'place', 'year', 'classification'];
            $q->where(function ($q) use ($keyword, $effectiveColumns) {
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
        });

        return $query->paginate($perPage)->withQueryString();
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
                    $q->where('resource_type', 'reference')->orWhereNull('resource_type');
                });
            } else {
                $query->where('resource_type', $parts[0]);
            }
        } else {
            $query->where(function ($q) use ($parts, $includeNullAsReference) {
                $q->whereIn('resource_type', $parts);
                if ($includeNullAsReference) {
                    $q->orWhereNull('resource_type');
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
