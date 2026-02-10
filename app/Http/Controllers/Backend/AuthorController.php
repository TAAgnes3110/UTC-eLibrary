<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorRequest;

use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
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
