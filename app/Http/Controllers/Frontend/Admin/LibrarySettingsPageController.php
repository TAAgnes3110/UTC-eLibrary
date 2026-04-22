<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LibrarySettingsPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/LibrarySettings/Index');
    }

    public function classifications(): Response
    {
        return Inertia::render('Admin/LibrarySettings/Classifications');
    }
}
