<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class WarehousePageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Warehouses/Index');
    }

    public function bookshelf(): Response
    {
        return Inertia::render('Admin/Warehouses/Bookshelf');
    }
}
