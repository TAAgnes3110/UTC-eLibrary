<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Loans/Index');
    }

    public function extensions(): Response
    {
        return Inertia::render('Admin/Loans/Extensions');
    }

    public function onsite(): Response
    {
        return Inertia::render('Admin/Loans/OnSite');
    }

    public function penalties(): Response
    {
        return Inertia::render('Admin/Loans/Penalties');
    }
}
