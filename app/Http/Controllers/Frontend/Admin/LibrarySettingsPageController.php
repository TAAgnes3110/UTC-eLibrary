<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class LibrarySettingsPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/LibrarySettings/Index');
    }

    public function classifications(): RedirectResponse
    {
        return redirect()->route('admin.classifications.index');
    }
}
