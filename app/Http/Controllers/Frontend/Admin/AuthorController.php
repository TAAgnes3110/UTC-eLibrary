<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuthorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Authors/Index');
    }

    public function trash(): JsonResponse
    {
        $items = Author::onlyTrashed()->orderByDesc('deleted_at')->get(['id', 'name', 'nationality', 'deleted_at'])->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'nationality' => $a->nationality,
            'deleted_at' => $a->deleted_at?->toIso8601String(),
        ]);
        return response()->json(['data' => $items]);
    }

    public function restore(int $id): JsonResponse
    {
        $author = Author::onlyTrashed()->find($id);
        if (!$author) {
            return response()->json(['status' => 'error'], 410);
        }
        $author->restore();
        return response()->json(['status' => 'success']);
    }

    public function forceDelete(int $id): JsonResponse
    {
        $author = Author::onlyTrashed()->find($id);
        if (!$author) {
            return response()->json(['status' => 'error'], 410);
        }
        $author->forceDelete();
        return response()->json(['status' => 'success']);
    }
}
