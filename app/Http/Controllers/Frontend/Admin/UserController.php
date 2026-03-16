<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function index(): Response
    {
        $payload = $this->userService->adminList(20);
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

        return inertia('Admin/Users/Index', [
            'users' => $users,
            'roles' => $payload['roles'],
        ]);
    }
}
