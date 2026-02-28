<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $tab = request('tab', 'category');
        return Inertia::render('Admin/Categories/Index', ['tab' => $tab]);
    }
}
