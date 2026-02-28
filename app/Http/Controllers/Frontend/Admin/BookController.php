<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Exports\BooksExport;
use App\Models\Book;
use App\Services\TaxonomyCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BookController extends Controller
{
    public function index(Request $request): Response
    {
        $books = Book::with(['authors', 'publisher', 'category'])
            ->withCount('copies')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'type' => $book->type instanceof \BackedEnum ? $book->type->value : ($book->type ?? 'book'),
                    'classification_code' => $book->classification_code,
                    'category_id' => $book->category_id,
                    'publication_place' => $book->publication_place,
                    'published_year' => $book->published_year,
                    'total_pages' => $book->total_pages,
                    'book_size' => $book->book_size,
                    'volume_number' => $book->volume_number,
                    'price' => $book->price,
                    'notes' => $book->notes,
                    'status' => $book->status ?? 'available',
                    'quantity' => $book->copies_count ?? $book->total_copies ?? 0,
                    'publisher_name' => $book->publisher?->name,
                    'publisher' => $book->publisher ? ['id' => $book->publisher->id, 'name' => $book->publisher->name] : null,
                    'authors' => $book->authors->map(fn($a) => ['id' => $a->id, 'name' => $a->name]),
                    'image_url' => $book->params['image_url'] ?? null,
                ];
            });

        $taxonomy = app(TaxonomyCacheService::class);
        return Inertia::render('Admin/Books/Index', [
            'books' => [
                'data' => $books,
                'total' => $books->count(),
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $books->count(),
                'from' => 1,
                'to' => $books->count(),
            ],
            'categories' => $taxonomy->getCategories(),
            'publishers' => $taxonomy->getPublishers(),
        ]);
    }

    public function trash(): JsonResponse
    {
        $items = Book::onlyTrashed()->orderByDesc('deleted_at')->get()->map(fn($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'classification_code' => $b->classification_code,
            'deleted_at' => $b->deleted_at?->toIso8601String(),
        ]);
        return response()->json(['data' => $items]);
    }

    public function restore(int $id): JsonResponse
    {
        $book = Book::onlyTrashed()->find($id);
        if (!$book) {
            return response()->json(['status' => 'error'], 410);
        }
        $book->restore();
        return response()->json(['status' => 'success']);
    }

    public function forceDelete(int $id): JsonResponse
    {
        $book = Book::onlyTrashed()->find($id);
        if (!$book) {
            return response()->json(['status' => 'error'], 410);
        }
        $book->forceDelete();
        return response()->json(['status' => 'success']);
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new BooksExport(), 'danh_sach_sach_tai_lieu.xlsx');
    }
}
