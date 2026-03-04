<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    use DecodesBackendResponse;

    public function index(Request $request): Response
    {
        $response = app(\App\Http\Controllers\Api\BookController::class)->adminPageData($request);
        $data = $this->backendData($response);
        $defaultBooks = ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 0, 'from' => null, 'to' => null];

        return Inertia::render('Admin/Books/Index', [
            'books' => $data['books'] ?? $defaultBooks,
            'categories' => $data['categories'] ?? [],
            'faculties' => $data['faculties'] ?? [],
            'departments' => $data['departments'] ?? [],
            'warehouses' => $data['warehouses'] ?? [],
            'cohorts' => $data['cohorts'] ?? [],
            'filters' => $data['filters'] ?? ['group' => null],
        ]);
    }
}
