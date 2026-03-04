<?php

namespace App\Http\Controllers\Api;

use App\Exports\BooksExport;
use App\Helpers\ApiResponse;
use App\Helpers\FileHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Chỉ điều hướng: gọi BookService, trả ApiResponse / Resource.
 */
class BookController extends Controller
{
    public function __construct(
        private BookService $bookService
    ) {}

    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,epub', 'max:51200'],
        ], [
            'file.required' => 'Vui lòng chọn file.',
            'file.mimes' => 'Chỉ chấp nhận: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, EPUB.',
            'file.max' => 'Dung lượng tối đa 50MB.',
        ]);
        $result = $this->bookService->uploadDocument($request->file('file'));
        return ApiResponse::success($result);
    }

    public function adminPageData(Request $request): JsonResponse
    {
        $group = $request->query('group');
        $perPage = (int) $request->input('per_page', 20);
        $payload = $this->bookService->adminPageData($group, $perPage);
        return ApiResponse::success($payload);
    }

    public function show(Book $book): JsonResponse
    {
        $book->load(['category', 'copies']);
        return ApiResponse::success($book);
    }

    public function index(Request $request): JsonResponse
    {
        $payload = $this->bookService->index(
            $request->input('keyword'),
            (int) ($request->input('per_page') ?? 10)
        );
        return ApiResponse::success($payload);
    }

    public function readerSearchPageData(Request $request): JsonResponse
    {
        $filters = $request->only(['q', 'category_id', 'type', 'year']);
        $payload = $this->bookService->readerSearchPageData($filters);
        return ApiResponse::success($payload);
    }

    public function readerBookShowData(Book $book): JsonResponse
    {
        $data = $this->bookService->readerBookShowData($book);
        return ApiResponse::success($data);
    }

    public function store(BookRequest $request): JsonResponse
    {
        $book = $this->bookService->create($request->validated());
        return ApiResponse::success($book, __('messages.success_create'), 201);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);
        try {
            $result = $this->bookService->import($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
        $code = match ($result['status']) {
            'success' => 200,
            'partial' => 207,
            default => 422,
        };
        $message = "Import hoàn tất: {$result['summary']['success']} thành công, {$result['summary']['skipped']} bỏ qua, {$result['summary']['errors']} lỗi.";
        return ApiResponse::json([
            'status' => $result['status'],
            'messages' => $message,
            'data' => $result,
        ], $code);
    }

    public function update(BookRequest $request, Book $book): JsonResponse
    {
        $book = $this->bookService->update($book, $request->validated());
        return ApiResponse::success($book, __('messages.success_update'));
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->bookService->destroy($book);
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function trash(Request $request): JsonResponse
    {
        $payload = $this->bookService->trash();
        return ApiResponse::success($payload);
    }

    public function restore($id): JsonResponse
    {
        $book = $this->bookService->restore($id);
        if (!$book) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    public function forceDelete($id): JsonResponse
    {
        if (!$this->bookService->forceDelete($id)) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, __('Đã xóa vĩnh viễn.'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new BooksExport(), 'danh_sach_sach_tai_lieu.xlsx');
    }

    /**
     * Tải file Excel mẫu cho thủ thư nhập kho (4 sheet: NhapSach, TheLoai, TheLoaiChiTiet, KhoSach).
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = FileHelpers::createLibraryTemplate();
        $writer = new Xlsx($spreadsheet);
        $filename = 'file_mau_nhap_kho_sach.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
