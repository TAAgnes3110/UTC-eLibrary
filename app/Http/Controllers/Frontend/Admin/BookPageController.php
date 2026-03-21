<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class BookPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Books/Index', [
            'pageKind' => 'print',
            'resourceKindFilter' => 'print,hybrid',
        ]);
    }

    public function digital(): Response
    {
        return Inertia::render('Admin/Books/Index', [
            'pageKind' => 'digital',
            'resourceKindFilter' => 'digital,hybrid',
        ]);
    }
}
