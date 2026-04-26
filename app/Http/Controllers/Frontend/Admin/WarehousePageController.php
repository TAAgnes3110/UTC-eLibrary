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

    public function storage(): Response
    {
        return Inertia::render('Admin/Warehouses/StorageCabinets');
    }

    public function storageCabinets(): Response
    {
        return Inertia::render('Admin/Warehouses/StorageCabinets');
    }

}
