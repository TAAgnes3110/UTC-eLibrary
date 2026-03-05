<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. Dữ liệu lấy từ Backend API. */
class CategoryController extends Controller
{
    use DecodesBackendResponse;

    public function index(Request $request): Response
    {
        $response = app(\App\Http\Controllers\Api\CategoryController::class)->adminList($request);
        $data = $this->backendData($response);

        return Inertia::render('Admin/Categories/Index', [
            'tab' => $data['tab'] ?? 'category',
            'categories' => $data['categories'] ?? [],
        ]);
    }
}
