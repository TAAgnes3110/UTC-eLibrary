<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. Dữ liệu lấy từ Backend API. */
class UserController extends Controller
{
    use DecodesBackendResponse;

    public function index(Request $request): Response
    {
        $response = app(UserController::class)->adminPageData($request);
        $data = $this->backendData($response);
        $rawUsers = $data['users'] ?? [];
        $meta = $rawUsers['meta'] ?? [];
        $users = [
            'data' => $rawUsers['data'] ?? [],
            'current_page' => $meta['current_page'] ?? 1,
            'last_page' => $meta['last_page'] ?? 1,
            'per_page' => $meta['per_page'] ?? 20,
            'total' => $meta['total'] ?? 0,
            'from' => $meta['from'] ?? null,
            'to' => $meta['to'] ?? null,
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $data['roles'] ?? [],
        ]);
    }
}
