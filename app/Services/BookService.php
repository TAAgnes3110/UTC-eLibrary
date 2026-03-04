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

    public function uploadDocument(UploadedFile $file): array
    {
        $ext = $file->getClientOriginalExtension() ?: 'pdf';
        $name = Str::uuid() . '.' . strtolower($ext);
        $path = $file->storeAs('documents', $name, 'public');
        $url = asset('storage/' . $path);
        return ['url' => $url, 'path' => $path];
    }

    public function adminPageData(?string $group, int $perPage): array
    {
        $taxonomy = app(TaxonomyCacheService::class);
        $query = Book::with(['category', 'faculty', 'department', 'warehouse'])
            ->withCount('copies')
            ->orderBy('updated_at', 'desc');

        if ($group === 'digital') {
            $query->where('is_digital', true);
        } elseif ($group !== null && $group !== '') {
            $types = BookType::getTypesByGroup($group);
            if (!empty($types)) {
                $query->whereIn('type', $types);
            }
        }

        $perPage = min(max($perPage, 5), 100);
        $paginator = $query->paginate($perPage)->withQueryString();
        $books = $paginator->getCollection()->map(function ($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'co_authors' => $book->co_authors,
                'type' => $book->type instanceof \BackedEnum ? $book->type->value : ($book->type ?? 'book'),
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
        });
        $paginator->setCollection($books);

        $faculties = Faculty::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $departments = Department::where('is_active', true)->orderBy('faculty_id')->orderBy('name')->get(['id', 'name', 'faculty_id']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'location']);
        $cohorts = $taxonomy->getCohorts();

        return [
            'books' => $paginator->toArray(),
            'categories' => $taxonomy->getCategories(),
            'warehouses' => $warehouses,
            // Không còn danh mục riêng cho tác giả/nhà xuất bản – nhập tay trong bảng sách
            'faculties' => $faculties,
            'departments' => $departments,
            'cohorts' => $cohorts ?? [],
            'filters' => ['group' => $group],
        ];
    }

    /** @return array{data: mixed, current_page: int, last_page: int, per_page: int, total: int, ...} */
    public function index(?string $keyword, int $perPage = 10): array
    {
        $query = Book::query()
            ->with(['category'])
            ->when($keyword !== null && $keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%$keyword%")
                        ->orWhere('classification_code', 'like', "%$keyword%")
                        ->orWhere('isbn', 'like', "%$keyword%");
                });
            })
            ->orderBy('id', 'desc');
        $paginator = $query->paginate($perPage)->withQueryString();
        return $paginator->toArray();
    }

    public function readerSearchPageData(array $filters): array
    {
        $query = Book::with(['category'])
            ->withCount('copies')
            ->whereNull('deleted_at');

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($qry) use ($q) {
                $qry->where('title', 'like', "%{$q}%")
                    ->orWhere('classification_code', 'like', "%{$q}%")
                    ->orWhere('author', 'like', "%{$q}%")
                    ->orWhere('co_authors', 'like', "%{$q}%");
            });
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        // Không lọc theo publisher_id nữa – dùng text publisher_name trong bảng books
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['year'])) {
            $query->where('published_year', $filters['year']);
        }

        $books = $query->orderBy('updated_at', 'desc')->paginate(12)->through(function ($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'co_authors' => $book->co_authors,
                'type' => $book->type instanceof \BackedEnum ? $book->type->value : ($book->type ?? 'book'),
                'classification_code' => $book->classification_code,
                'category_name' => $book->category?->name,
                'publication_place' => $book->publication_place,
                'published_year' => $book->published_year,
                'publisher_name' => $book->publisher_name,
                'quantity' => $book->copies_count ?? 0,
                'image_url' => $book->params['image_url'] ?? null,
            ];
        });

        $taxonomy = app(TaxonomyCacheService::class);
        return [
            'books' => $books,
            'categories' => $taxonomy->getCategories(),
            // Không còn danh mục riêng cho nhà xuất bản – lấy từ dữ liệu sách
            'filters' => $filters,
        ];
    }

    public function readerBookShowData(Book $book): array
    {
        $book->load(['category', 'copies']);
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'co_authors' => $book->co_authors,
            'type' => $book->type instanceof \BackedEnum ? $book->type->value : ($book->type ?? 'book'),
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
        $paginator = Book::onlyTrashed()
            ->with(['category'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
        $items = $paginator->getCollection()->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'classification_code' => $b->classification_code,
            'deleted_at' => $b->deleted_at?->toIso8601String(),
        ]);
        $paginator->setCollection($items);
        return $paginator->toArray();
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
