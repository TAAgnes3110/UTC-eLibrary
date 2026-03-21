<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Reader/Dashboard');
    }

    public function search(Request $request): Response
    {
        return Inertia::render('Reader/Books/Index', [
            'filters' => [
                'q' => $request->input('q'),
            ],
        ]);
    }

    public function loans(): Response
    {
        return Inertia::render('Reader/Loans/Index');
    }
}
