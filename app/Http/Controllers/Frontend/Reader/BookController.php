<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\TaxonomyCacheService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function search(Request $request): Response
    {
        $filters = $request->only(['q', 'category_id', 'publisher_id', 'type', 'year']);
        $query = Book::with(['authors', 'publisher', 'category'])
            ->withCount('copies')
            ->whereNull('deleted_at');

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($qry) use ($q) {
                $qry->where('title', 'like', "%{$q}%")
                    ->orWhere('classification_code', 'like', "%{$q}%")
                    ->orWhereHas('authors', fn($a) => $a->where('name', 'like', "%{$q}%"));
            });
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['publisher_id'])) {
            $query->where('publisher_id', $filters['publisher_id']);
        }
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
                'type' => $book->type instanceof \BackedEnum ? $book->type->value : ($book->type ?? 'book'),
                'classification_code' => $book->classification_code,
                'category_name' => $book->category?->name,
                'publication_place' => $book->publication_place,
                'published_year' => $book->published_year,
                'publisher_name' => $book->publisher?->name,
                'authors' => $book->authors->map(fn($a) => ['id' => $a->id, 'name' => $a->name]),
                'quantity' => $book->copies_count ?? 0,
                'image_url' => $book->params['image_url'] ?? null,
            ];
        });

        $taxonomy = app(TaxonomyCacheService::class);
        return Inertia::render('Reader/Search/Index', [
            'books' => $books,
            'categories' => $taxonomy->getCategories(),
            'publishers' => $taxonomy->getPublishers(),
            'filters' => $filters,
        ]);
    }

    public function show(Book $book): Response
    {
        $book->load(['authors', 'publisher', 'category', 'copies']);
        return Inertia::render('Reader/Books/Show', [
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'type' => $book->type instanceof \BackedEnum ? $book->type->value : ($book->type ?? 'book'),
                'classification_code' => $book->classification_code,
                'category_name' => $book->category?->name,
                'description' => $book->notes,
                'publication_place' => $book->publication_place,
                'published_year' => $book->published_year,
                'total_pages' => $book->total_pages,
                'publisher_name' => $book->publisher?->name,
                'authors' => $book->authors->map(fn($a) => ['id' => $a->id, 'name' => $a->name]),
                'quantity' => $book->copies->count(),
                'image_url' => $book->params['image_url'] ?? null,
                'ebook_url' => $book->params['ebook_url'] ?? null,
                'audio_url' => $book->params['audio_url'] ?? null,
                'detail_url' => $book->params['detail_url'] ?? null,
                'marc' => $book->params['marc'] ?? null,
                'copies' => $book->copies->map(fn($c) => ['id' => $c->id, 'barcode' => $c->barcode ?? $c->id, 'status' => $c->status ?? 'available']),
            ],
        ]);
    }
}
