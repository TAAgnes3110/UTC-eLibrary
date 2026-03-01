<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. Dữ liệu do Vue gọi API. */
class PublisherController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Publishers/Index');
    }
}
