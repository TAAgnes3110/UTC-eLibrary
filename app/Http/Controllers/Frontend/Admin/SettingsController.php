<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang / redirect. */
class SettingsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.settings.rules');
    }

    public function rules(): Response
    {
        return Inertia::render('Admin/Settings/LoanRules');
    }

    public function content(): Response
    {
        return Inertia::render('Admin/Settings/Content');
    }

    public function appearance(): Response
    {
        return Inertia::render('Admin/Settings/Appearance');
    }
}
