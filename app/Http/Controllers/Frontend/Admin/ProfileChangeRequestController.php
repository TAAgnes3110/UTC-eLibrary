<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. Dữ liệu từ Backend API. Duyệt/Từ chối do Vue gọi API. */
class ProfileChangeRequestController extends Controller
{
    use DecodesBackendResponse;

    public function index(Request $request): Response
    {
        $response = app(\App\Http\Controllers\Api\ProfileChangeRequestController::class)->index($request);
        $data = $this->backendData($response);

        $requests = $data['data'] ?? [];
        $meta = $data['meta'] ?? [];
        $currentPage = $meta['current_page'] ?? 1;
        $lastPage = $meta['last_page'] ?? 1;
        $path = $request->url();
        $status = $request->input('status', 'pending');
        $requestsPaginated = (object) [
            'data' => $requests,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'prev_page_url' => $currentPage > 1 ? $path . '?status=' . $status . '&page=' . ($currentPage - 1) : null,
            'next_page_url' => $currentPage < $lastPage ? $path . '?status=' . $status . '&page=' . ($currentPage + 1) : null,
        ];

        return Inertia::render('Admin/ProfileChangeRequests/Index', [
            'requests' => $requestsPaginated,
            'faculties' => $data['faculties'] ?? [],
            'departments' => $data['departments'] ?? [],
            'statusFilter' => $data['statusFilter'] ?? 'pending',
        ]);
    }
}
