<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function slips(): Response
    {
        return Inertia::render('Admin/Library/Slips');
    }

    public function liquidation(): Response
    {
        return Inertia::render('Admin/Library/Liquidation');
    }

    public function inventory(): Response
    {
        return Inertia::render('Admin/Library/Inventory');
    }
}
