<?php

namespace App\Services;

use App\Enums\BookType;
use App\Helpers\BookHelper;
use App\Helpers\FileHelpers;
use App\Imports\BooksImport;
use App\Models\Book;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class BookService
{
    private const TRASH_PER_PAGE = 50;

    private const ADMIN_PER_PAGE_MIN = 5;

    private const ADMIN_PER_PAGE_MAX = 100;

    /** Chuẩn hóa enum type sang string. */
    private static function enumToString(mixed $type): string
    {
        return $type instanceof \BackedEnum ? $type->value : ($type ?? 'book');
    }

    /** Map Book → mảng cho admin. */
    private static function toAdminArray(Book $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'co_authors' => $book->co_authors,
            'type' => self::enumToString($book->type),
            'is_digital' => (bool) ($book->is_digital ?? false),
            'classification_code' => $book->classification_code,
            'category_id' => $book->category_id,
            'faculty_id' => $book->faculty_id,
            'department_id' => $book->department_id,
            'cohort' => $book->cohort,
            'faculty' => $book->faculty ? ['id' => $book->faculty->id, 'name' => $book->faculty->name] : null,
            'department' => $book->department ? ['id' => $book->department->id, 'name' => $book->department->name] : null,
            'publication_place' => $book->publication_place,
            'published_year' => $book->published_year,
            'total_pages' => $book->total_pages,
            'book_size' => $book->book_size,
            'volume_number' => $book->volume_number,
            'price' => $book->price,
            'notes' => $book->notes,
            'status' => $book->status ?? 'available',
            'quantity' => $book->copies_count ?? $book->total_copies ?? 0,
            'warehouse_id' => $book->warehouse_id,
            'shelf' => $book->params['shelf'] ?? null,
            'publisher_name' => $book->publisher_name,
            'image_url' => $book->params['image_url'] ?? null,
            'file_url' => $book->file_url,
        ];
    }

    public function uploadDocument(UploadedFile $file): array
    {
        $ext = $file->getClientOriginalExtension() ?: 'pdf';
        $name = Str::uuid() . '.' . strtolower($ext);
        $path = $file->storeAs('documents', $name, 'public');
        $url = asset('storage/' . $path);
        return ['url' => $url, 'path' => $path];
    }

    /**
     * Danh sách sách cho admin (phân trang + master data).
     *
     * @param array{group?: string, q?: string, status?: string, search_in?: string, per_page?: int} $params
     */
    public function adminList(array $params): array
    {
        $group = $params['group'] ?? null;
        $perPage = min(max((int) ($params['per_page'] ?? 20), self::ADMIN_PER_PAGE_MIN), self::ADMIN_PER_PAGE_MAX);
        $q = trim((string) ($params['q'] ?? ''));
        $status = $params['status'] ?? null;
        $searchIn = isset($params['search_in']) ? array_filter(explode(',', $params['search_in'])) : [];

        $query = $this->buildAdminQuery($group, $q, $status, $searchIn);
        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->through(fn (Book $book) => self::toAdminArray($book));

        $taxonomy = app(TaxonomyCacheService::class);

        return [
            'books' => $paginator->toArray(),
            'categories' => $taxonomy->getCategories(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'location']),
            'faculties' => Faculty::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('faculty_id')->orderBy('name')->get(['id', 'name', 'faculty_id']),
            'cohorts' => $taxonomy->getCohorts() ?? [],
            'filters' => ['group' => $group, 'q' => $q, 'status' => $status],
        ];
    }

    /** Build query sách admin (group, keyword, status, search_in). */
    private function buildAdminQuery(?string $group, string $q, ?string $status, array $searchIn): \Illuminate\Database\Eloquent\Builder
    {
        $query = Book::with(['category:id,name', 'faculty:id,name', 'department:id,name,faculty_id', 'warehouse:id,code,name,location'])
            ->withCount('copies')
            ->orderByDesc('updated_at');

        if ($group === 'digital') {
            $query->where('is_digital', true);
        } elseif ($group !== null && $group !== '') {
            $types = BookType::getTypesByGroup($group);
            if (!empty($types)) {
                $query->whereIn('type', $types);
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($q !== '' && !empty($searchIn)) {
            $keyword = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(function ($qb) use ($keyword, $searchIn) {
                foreach ($searchIn as $i => $col) {
                    $isFirst = $i === 0;
                    $method = $isFirst ? 'where' : 'orWhere';
                    $add = match ($col) {
                        'title' => fn ($q) => $q->where('title', 'like', $keyword),
                        'author' => fn ($q) => $q->where(fn ($s) => $s->where('author', 'like', $keyword)->orWhere('co_authors', 'like', $keyword)),
                        'category' => fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', 'like', $keyword)),
                        'classification_code' => fn ($q) => $q->where('classification_code', 'like', $keyword),
                        'publisher' => fn ($q) => $q->where('publisher_name', 'like', $keyword),
                        'type' => fn ($q) => $q->where('type', 'like', $keyword),
                        default => null,
                    };
                    if ($add) {
                        $qb->{$method}($add);
                    }
                }
            });
        } elseif ($q !== '') {
            $keyword = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(function ($qb) use ($keyword) {
                $qb->where('title', 'like', $keyword)
                    ->orWhere('author', 'like', $keyword)
                    ->orWhere('co_authors', 'like', $keyword)
                    ->orWhere('classification_code', 'like', $keyword)
                    ->orWhere('publisher_name', 'like', $keyword);
            });
        }

        return $query;
    }

    /** API index: tìm sách theo keyword (title, classification_code, isbn). */
    public function index(?string $keyword, int $perPage = 10): array
    {
        $keyword = trim((string) ($keyword ?? ''));
        $keywordEscaped = $keyword !== '' ? '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%' : null;

        $query = Book::with(['category:id,name'])
            ->when($keywordEscaped, fn ($q) => $q->where(function ($sub) use ($keywordEscaped) {
                $sub->where('title', 'like', $keywordEscaped)
                    ->orWhere('classification_code', 'like', $keywordEscaped)
                    ->orWhere('isbn', 'like', $keywordEscaped);
            }))
            ->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString()->toArray();
    }

    public function readerSearch(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? ''));
        $keywordEscaped = $keyword !== '' ? '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%' : null;

        $books = Book::with(['category:id,name'])
            ->withCount('copies')
            ->when($keywordEscaped, fn ($q) => $q->where(function ($sub) use ($keywordEscaped) {
                $sub->where('title', 'like', $keywordEscaped)
                    ->orWhere('classification_code', 'like', $keywordEscaped)
                    ->orWhere('author', 'like', $keywordEscaped)
                    ->orWhere('co_authors', 'like', $keywordEscaped);
            }))
            ->when(!empty($filters['category_id']), fn ($q) => $q->where('category_id', $filters['category_id']))
            ->when(!empty($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['year']), fn ($q) => $q->where('published_year', $filters['year']))
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->through(fn (Book $book) => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'co_authors' => $book->co_authors,
                'type' => self::enumToString($book->type),
                'classification_code' => $book->classification_code,
                'category_name' => $book->category?->name,
                'publication_place' => $book->publication_place,
                'published_year' => $book->published_year,
                'publisher_name' => $book->publisher_name,
                'quantity' => $book->copies_count ?? 0,
                'image_url' => $book->params['image_url'] ?? null,
            ]);

        return [
            'books' => $books,
            'categories' => app(TaxonomyCacheService::class)->getCategories(),
            'filters' => $filters,
        ];
    }

    public function readerShow(Book $book): array
    {
        $book->load(['category:id,name', 'copies:id,book_id,barcode,status']);
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'co_authors' => $book->co_authors,
            'type' => self::enumToString($book->type),
            'classification_code' => $book->classification_code,
            'category_name' => $book->category?->name,
            'description' => $book->notes,
            'publication_place' => $book->publication_place,
            'published_year' => $book->published_year,
            'total_pages' => $book->total_pages,
            'publisher_name' => $book->publisher_name,
            'quantity' => $book->copies->count(),
            'image_url' => $book->params['image_url'] ?? null,
            'ebook_url' => $book->params['ebook_url'] ?? null,
            'audio_url' => $book->params['audio_url'] ?? null,
            'detail_url' => $book->params['detail_url'] ?? null,
            'marc' => $book->params['marc'] ?? null,
            'copies' => $book->copies->map(fn ($c) => ['id' => $c->id, 'barcode' => $c->barcode ?? $c->id, 'status' => $c->status ?? 'available']),
        ];
    }

    public function destroy(Book $book): void
    {
        $book->delete();
    }

    public function trash(int $perPage = self::TRASH_PER_PAGE): array
    {
        return Book::onlyTrashed()
            ->with(['category:id,name'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'classification_code' => $b->classification_code,
                'deleted_at' => $b->deleted_at?->toIso8601String(),
            ])
            ->toArray();
    }

    /** @return Book|null */
    public function restore(int|string $id): ?Book
    {
        $book = Book::onlyTrashed()->find($id);
        if (!$book) {
            return null;
        }
        $book->restore();
        return $book;
    }

    public function forceDelete(int|string $id): bool
    {
        $book = Book::onlyTrashed()->find($id);
        if (!$book) {
            return false;
        }
        $book->forceDelete();
        return true;
    }

    public function create(array $data): Book
    {
        $params = isset($data['shelf']) ? ['shelf' => $data['shelf']] : [];
        $book = Book::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'author' => $data['author'] ?? null,
            'co_authors' => $data['co_authors'] ?? null,
            'isbn' => $data['isbn'] ?? null,
            'classification_code' => $data['classification_code'] ?? null,
            'classification_detail' => $data['classification_detail'] ?? null,
            'edition' => $data['edition'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'cohort' => $data['cohort'] ?? null,
            'publisher_name' => $data['publisher'] ?? null,
            'params' => $params,
            'publication_place' => $data['publication_place'] ?? null,
            'published_year' => $data['published_year'] ?? null,
            'total_pages' => $data['total_pages'] ?? null,
            'book_size' => $data['book_size'] ?? null,
            'volume_number' => $data['volume_number'] ?? null,
            'price' => $data['price'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'available',
            'is_digital' => (bool) ($data['is_digital'] ?? false),
            'file_url' => $data['file_url'] ?? null,
        ]);

        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity > 0) {
            BookHelper::createCopies($book, $quantity);
        }
        $book->updateStatistics();

        return $book->load(['category']);
    }

    public function update(Book $book, array $data): Book
    {
        $params = $book->params ?? [];
        if (array_key_exists('shelf', $data)) {
            $params['shelf'] = $data['shelf'];
        }
        $book->update([
            'type' => $data['type'],
            'title' => $data['title'],
            'author' => $data['author'] ?? $book->author,
            'co_authors' => $data['co_authors'] ?? $book->co_authors,
            'isbn' => $data['isbn'] ?? null,
            'classification_code' => $data['classification_code'] ?? null,
            'classification_detail' => $data['classification_detail'] ?? null,
            'edition' => $data['edition'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? $book->warehouse_id,
            'cohort' => $data['cohort'] ?? null,
            'publisher_name' => $data['publisher'] ?? $book->publisher_name,
            'params' => $params,
            'publication_place' => $data['publication_place'] ?? null,
            'published_year' => $data['published_year'] ?? null,
            'total_pages' => $data['total_pages'] ?? null,
            'book_size' => $data['book_size'] ?? null,
            'volume_number' => $data['volume_number'] ?? null,
            'price' => $data['price'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? $book->status,
            'is_digital' => array_key_exists('is_digital', $data) ? (bool) $data['is_digital'] : $book->is_digital,
            'file_url' => $data['file_url'] ?? $book->file_url,
        ]);

        $quantity = (int) ($data['quantity'] ?? 0);
        $currentCopies = $book->copies()->count();
        if ($quantity > $currentCopies) {
            BookHelper::createCopies($book, $quantity - $currentCopies);
        }
        $book->updateStatistics();

        return $book->load(['category']);
    }

    public function import(UploadedFile $file): array
    {
        if (!FileHelpers::isExcelFile($file)) {
            throw new \InvalidArgumentException('File phải có định dạng: ' . implode(', ', FileHelpers::EXCEL_EXTENSIONS));
        }
        return (new BooksImport())->import($file);
    }
}
