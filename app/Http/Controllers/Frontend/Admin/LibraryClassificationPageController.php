<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LibraryClassificationPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Classifications/Index');
    }
}

