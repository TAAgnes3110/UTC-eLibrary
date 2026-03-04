<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    use DecodesBackendResponse;

    public function search(Request $request): Response
    {
        $response = app(\App\Http\Controllers\Api\BookController::class)->readerSearchPageData($request);
        $data = $this->backendData($response);

        return Inertia::render('Reader/Search/Index', [
            'books' => $data['books'] ?? [],
            'categories' => $data['categories'] ?? [],
            'filters' => $data['filters'] ?? [],
        ]);
    }

    public function show(Book $book): Response
    {
        $response = app(\App\Http\Controllers\Api\BookController::class)->readerBookShowData($book);
        $bookData = $this->backendData($response);

        return Inertia::render('Reader/Books/Show', [
            'book' => $bookData,
        ]);
    }
}
