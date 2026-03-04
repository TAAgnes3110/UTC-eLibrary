<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. Dữ liệu lấy trực tiếp từ UserService (không qua API) để tránh lỗi decode. */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $payload = app(UserService::class)->adminPageData(20);
        $paginator = $payload['users'];
        $items = UserResource::collection($paginator->getCollection())->resolve();
        $users = [
            'data' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $payload['roles'],
        ]);
    }
}
