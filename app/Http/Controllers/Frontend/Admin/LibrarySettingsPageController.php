<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LibrarySettingsPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/LibrarySettings/Index');
    }

    public function pricing(): Response
    {
        return Inertia::render('Admin/LibrarySettings/Pricing');
    }

    public function classifications(): RedirectResponse
    {
        return redirect()->route('admin.classifications.index');
    }
}
