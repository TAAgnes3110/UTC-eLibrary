<?php

namespace App\Http\Controllers\Backend;

use App\Enums\BookType;
use App\Helpers\FileHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Imports\BooksImport;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Publisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookController extends Controller
{
  /**
   * Danh sách sách có phân trang, tìm theo từ khóa.
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request): JsonResponse
  {
    $keyword = $request->input('keyword');
    $items = Book::query()
      ->with(['category', 'publisher', 'authors'])
      ->when($keyword, function ($query) use ($keyword) {
        $query->where(function ($q) use ($keyword) {
          $q->where('title', 'like', "%$keyword%")
            ->orWhere('classification_code', 'like', "%$keyword%");
        });
      })
      ->orderBy('id', 'desc')
      ->paginate(10)
      ->withQueryString();
    return $this->jsonResponse($items->toArray());
  }

  /**
   * Thêm sách mới (kèm tác giả, NXB, bản in).
   *
   * @param BookRequest $request
   * @return JsonResponse
   */
  public function store(BookRequest $request): JsonResponse
  {
    $data = $request->validated();
    $publisherId = $this->resolvePublisherId($data);
    $authorIds = $this->resolveAuthorIds($data);

    $book = Book::create([
      'type' => $data['type'],
      'title' => $data['title'],
      'classification_code' => $data['classification_code'] ?? null,
      'classification_detail' => $data['classification_detail'] ?? null,
      'category_id' => $data['category_id'] ?? null,
      'faculty_id' => $data['faculty_id'] ?? null,
      'publisher_id' => $publisherId,
      'publication_place' => $data['publication_place'] ?? null,
      'published_year' => $data['published_year'] ?? null,
      'total_pages' => $data['total_pages'] ?? null,
      'book_size' => $data['book_size'] ?? null,
      'volume_number' => $data['volume_number'] ?? null,
      'price' => $data['price'] ?? null,
      'notes' => $data['notes'] ?? null,
      'status' => $data['status'] ?? 'available',
    ]);

    $this->syncAuthors($book, $authorIds);
    $quantity = (int) ($data['quantity'] ?? 0);
    if ($quantity > 0) {
      $this->createCopies($book, $quantity);
    }
    $book->updateStatistics();

    return $this->jsonResponse([
      'status' => 'success',
      'message' => __('messages.success'),
      'data' => $book->load(['category', 'publisher', 'authors']),
    ], 201);
  }

  /**
   * Import danh sách sách từ file Excel/CSV.
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function import(Request $request): JsonResponse
  {
    $request->validate([
      'file' => 'required|file|max:10240',
    ]);

    $file = $request->file('file');

    if (!FileHelpers::isExcelFile($file)) {
      return $this->jsonResponse([
        'status' => 'error',
        'messages' => 'File phải có định dạng: ' . implode(', ', FileHelpers::EXCEL_EXTENSIONS),
      ], 422);
    }

    $importer = new BooksImport();
    $result = $importer->import($file);

    $code = match ($result['status']) {
      'success' => 200,
      'partial' => 207,
      default   => 422,
    };

    return $this->jsonResponse([
      'status' => $result['status'],
      'messages' => "Import hoàn tất: {$result['summary']['success']} thành công, {$result['summary']['skipped']} bỏ qua, {$result['summary']['errors']} lỗi.",
      'data' => $result,
    ], $code);
  }

  /**
   * Cập nhật thông tin sách (kèm tác giả, NXB, số bản in).
   *
   * @param BookRequest $request
   * @param Book $book
   * @return JsonResponse
   */
  public function update(BookRequest $request, Book $book): JsonResponse
  {
    $data = $request->validated();
    $publisherId = $this->resolvePublisherId($data);
    $authorIds = $this->resolveAuthorIds($data);

    $book->update([
      'type' => $data['type'],
      'title' => $data['title'],
      'classification_code' => $data['classification_code'] ?? null,
      'classification_detail' => $data['classification_detail'] ?? null,
      'category_id' => $data['category_id'] ?? null,
      'faculty_id' => $data['faculty_id'] ?? null,
      'publisher_id' => $publisherId,
      'publication_place' => $data['publication_place'] ?? null,
      'published_year' => $data['published_year'] ?? null,
      'total_pages' => $data['total_pages'] ?? null,
      'book_size' => $data['book_size'] ?? null,
      'volume_number' => $data['volume_number'] ?? null,
      'price' => $data['price'] ?? null,
      'notes' => $data['notes'] ?? null,
      'status' => $data['status'] ?? $book->status,
    ]);

    $this->syncAuthors($book, $authorIds);

    $quantity = (int) ($data['quantity'] ?? 0);
    $currentCopies = $book->copies()->count();
    if ($quantity > $currentCopies) {
      $this->createCopies($book, $quantity - $currentCopies);
    }
    $book->updateStatistics();

    return $this->jsonResponse([
      'status' => 'success',
      'message' => __('messages.success'),
      'data' => $book->load(['category', 'publisher', 'authors']),
    ]);
  }

  /**
   * Xóa mềm sách.
   *
   * @param Book $book
   * @return JsonResponse
   */
  public function destroy(Book $book): JsonResponse
  {
    $book->delete();
    return $this->jsonResponse([
      'status' => 'success',
      'message' => __('messages.success_delete'),
    ]);
  }

  /**
   * Danh sách sách đã xóa mềm (thùng rác).
   */
  public function trash(): JsonResponse
  {
    $items = Book::onlyTrashed()
      ->with(['category', 'publisher', 'authors'])
      ->orderByDesc('deleted_at')
      ->get()
      ->map(fn ($b) => [
        'id' => $b->id,
        'title' => $b->title,
        'classification_code' => $b->classification_code,
        'deleted_at' => $b->deleted_at?->toIso8601String(),
      ]);
    return $this->jsonResponse(['data' => $items]);
  }

  /**
   * Khôi phục sách từ thùng rác.
   */
  public function restore($id): JsonResponse
  {
    $book = Book::onlyTrashed()->find($id);
    if (!$book) {
      return $this->jsonResponse(['status' => 'error', 'message' => __('messages.error_410')], 410);
    }
    $book->restore();
    return $this->jsonResponse(['status' => 'success', 'message' => __('Đã khôi phục.')]);
  }

  /**
   * Xóa vĩnh viễn sách.
   */
  public function forceDelete($id): JsonResponse
  {
    $book = Book::onlyTrashed()->find($id);
    if (!$book) {
      return $this->jsonResponse(['status' => 'error', 'message' => __('messages.error_410')], 410);
    }
    $book->forceDelete();
    return $this->jsonResponse(['status' => 'success', 'message' => __('Đã xóa vĩnh viễn.')]);
  }

  /**
   * Xác định publisher_id: ưu tiên id → tên (tìm hoặc tạo) → NXB mặc định (giáo trình, BCKH, bài luận).
   *
   * @param array $data
   * @return int|null
   */
  private function resolvePublisherId(array $data): ?int
  {
    if (!empty($data['publisher_id'])) {
      return (int) $data['publisher_id'];
    }
    $name = trim((string) ($data['publisher'] ?? ''));
    if ($name !== '') {
      $publisher = Publisher::firstOrCreate(
        ['name' => $name],
        ['country' => 'Việt Nam', 'is_active' => true]
      );
      return $publisher->id;
    }
    $type = BookType::tryFrom($data['type'] ?? '');
    if ($type && $type->useDefaultPublisher()) {
      $publisher = Publisher::getOrCreateDefaultPublisher();
      return $publisher->id;
    }
    return null;
  }

  /**
   * Lấy danh sách id tác giả từ ô tác giả chính + đồng tác giả (phân tách bằng dấu phẩy/chấm phẩy).
   *
   * @param array $data
   * @return array<int>
   */
  private function resolveAuthorIds(array $data): array
  {
    $main = trim((string) ($data['author'] ?? ''));
    $co = trim((string) ($data['co_authors'] ?? ''));
    $names = array_filter(array_map('trim', array_merge([$main], $co !== '' ? preg_split('/[,;]+/u', $co) : [])));
    $names = array_unique($names);
    $ids = [];
    foreach ($names as $name) {
      if ($name === '') {
        continue;
      }
      $author = Author::firstOrCreate(['name' => $name], ['name' => $name]);
      $ids[] = $author->id;
    }
    return $ids;
  }

  /**
   * Đồng bộ quan hệ sách – tác giả (vai trò, thứ tự).
   *
   * @param Book $book
   * @param array<int> $authorIds
   * @return void
   */
  private function syncAuthors(Book $book, array $authorIds): void
  {
    $sync = [];
    foreach (array_values($authorIds) as $order => $id) {
      $sync[$id] = ['role' => $order === 0 ? 'author' : 'co-author', 'order' => $order];
    }
    $book->authors()->sync($sync);
  }

  /**
   * Tạo thêm bản in cho sách (mã vạch BK-{id}-{seq}).
   *
   * @param Book $book
   * @param int $count
   * @return void
   */
  private function createCopies(Book $book, int $count): void
  {
    $existing = BookCopy::withTrashed()->where('book_id', $book->id)->count();
    $prefix = 'BK-' . $book->id . '-';
    for ($i = 0; $i < $count; $i++) {
      $seq = $existing + $i + 1;
      $barcode = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
      if (BookCopy::where('barcode', $barcode)->exists()) {
        $barcode = $barcode . '-' . Str::random(6);
      }
      BookCopy::create([
        'book_id' => $book->id,
        'barcode' => $barcode,
        'status' => 'available',
        'condition' => 'good',
      ]);
    }
  }
}
