<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorRequest;
use App\Imports\AuthorsImport;

use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    /**
     * Import tác giả từ file Excel.
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

        $importer = new AuthorsImport();
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

    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $items = Author::query()
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('id', 'like', "%$keyword%")
                        ->orWhere('name', 'like', "%$keyword%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();
        return $this->jsonResponse($items->toArray());
    }


    public function store(AuthorRequest $request): JsonResponse
    {
        $data = $request->except(['id']);
        $author = Author::create($data);
        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_update'),
        ]);
    }

    public function update(AuthorRequest $request, $id): JsonResponse
    {
        $item = Author::find($id);
        if (!$item) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('messages.error_410'),
                'data' => [],
            ], 404);
        }
        $data = $request->validated();
        $item->update($data);
        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_update'),
            'data' => $item,
        ], 200);
    }

    public function destroy($id): JsonResponse
    {
        $item = Author::query()->find($id);
        if ($item) {
            $item->delete();
            return $this->jsonResponse([
                'status' => 'success',
                'messages' => __('messages.success_delete'),
            ], 200);
        } else {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('messages.error_410'),
                'data' => [],
            ], 410);
        }
    }
}
