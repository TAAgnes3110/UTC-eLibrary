<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileHelpers;
use App\Http\Controllers\Controller;
use App\Imports\BooksImport;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $keyword = $request->input('keyword');
    $items = Book::query()
      ->with(['category', 'authors'])
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
   * Import sách từ file Excel.
   */
  public function import(Request $request): JsonResponse
  {
    $request->validate([
      'file' => 'required|file|max:10240', // max 10MB
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
}
