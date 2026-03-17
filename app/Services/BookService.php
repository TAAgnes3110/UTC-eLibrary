<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Helpers\ImageUploadHelper;
use App\Exports\BookFileMauExport;
use App\Jobs\ProcessBookImport;
use App\Models\Book;
use App\Models\ClassificationDetail;
use App\Models\Import;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

class BookService
{
    private const PER_PAGE = 50;

    public function create(array $data): Book
    {
        return DB::transaction(function () use ($data) {
            $bookData = $data;
            $warehouse = Warehouse::findOrFail($bookData['warehouse_id']);
            if (empty($bookData['registration_number'])) {
                $bookData['registration_number'] = $this->generateRegistrationNumber($warehouse);
            }
            if (!empty($bookData['classification_detail_id']) && empty($bookData['book_code'])) {
                $classificationDetail = ClassificationDetail::findOrFail($bookData['classification_detail_id']);
                $bookData['book_code'] = $this->generateBookCode($classificationDetail, $warehouse);
            }
            return Book::create($bookData);
        });
    }

    public function update(Book $book, array $data): Book
    {
        unset(
            $data['id'],
            $data['created_at'],
            $data['updated_at'],
        );
        if (array_key_exists('cover_image', $data) && empty($data['cover_image'])) {
            unset($data['cover_image']);
        }
        $book->update($data);
        return $book;
    }

    public function index(?string $keyword, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = Book::query()
            ->with([
                'classification:id,code,name',
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
            ])
            ->when($keyword !== null && $keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('book_code', 'like', "%{$keyword}%")
                        ->orWhere('published_year', 'like', "%{$keyword}%")
                        ->orWhere('quantity', 'like', "%{$keyword}%");
                    $q->orWhereHas('authors', function ($sub) use ($keyword) {
                        $sub->where('name', 'like', "%{$keyword}%");
                    });
                    $q->orWhereHas('publishers', function ($sub) use ($keyword) {
                        $sub->where('name', 'like', "%{$keyword}%");
                    });
                    $q->orWhereHas('classification', function ($sub) use ($keyword) {
                        $sub->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%");
                    });
                    $q->orWhereHas('classificationDetail', function ($sub) use ($keyword) {
                        $sub->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%");
                    });
                    $q->orWhereHas('warehouse', function ($sub) use ($keyword) {
                        $sub->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%");
                    });
                });
            });
        return $query->paginate($perPage)->withQueryString();
    }

    public function destroy(Book $book): void
    {
        $book->delete();
    }

    public function trash(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return Book::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function restore(int $id): ?Book
    {
        $book = Book::onlyTrashed()->find($id);
        if (!$book) {
            return null;
        }
        $book->restore();
        return $book;
    }

    public function forceDelete(int $id): bool
    {
        $book = Book::onlyTrashed()->find($id);
        if (!$book) {
            return false;
        }
        $book->forceDelete();
        return true;
    }
    public function updateAvatar(Book $book, UploadedFile $file): ?array
    {
        if (!$file->isValid()) {
            return null;
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (!in_array($ext, ImageUploadHelper::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(__('Chỉ chấp nhận ảnh: ') . implode(', ', ImageUploadHelper::ALLOWED_EXTENSIONS) . '.');
        }
        $path = ImageUploadHelper::updateModelImage(
            $book,
            $file,
            'books',
            'cover_image',
            $book->code ?: (string) $book->id
        );
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

    /**
     * Import danh sách sách từ file Excel (chạy nền qua queue).
     *
     * @param UploadedFile $file
     * @return array{import_id:int,status:string}
     */
    public function importBooks(UploadedFile $file): array
    {
        $storedPath = $file->store('imports/books', 'local');

        $import = Import::create([
            'type' => ImportType::BOOK,
            'status' => ImportStatus::PENDING,
            'file_path' => $storedPath,
            'created_by' => Auth::id(),
        ]);

        ProcessBookImport::dispatch($import);

        return [
            'import_id' => $import->id,
            'status' => $import->status->value,
        ];
    }

    public function exportBooks(?array $ids = null): BinaryFileResponse
    {
        $export = new BookFileMauExport($ids);
        return Excel::download(
            $export,
            'FileSach.xlsx'
        );
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
}