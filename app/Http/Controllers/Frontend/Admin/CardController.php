<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. Dữ liệu readers từ Backend API, master data từ MasterData API. */
class CardController extends Controller
{
    use DecodesBackendResponse;

    public function index(Request $request): Response
    {
        $userData = $this->backendData(app(UserController::class)->readersPageData());
        $readers = $userData['data'] ?? $userData['readers'] ?? [];

        $masterData = $this->backendData(app(MasterDataController::class)->index($request));

        return Inertia::render('Admin/Cards/Index', [
            'readers' => $readers,
            'faculties' => $masterData['faculties'] ?? [],
            'cohorts' => $masterData['cohorts'] ?? [],
            'departments' => $masterData['departments'] ?? [],
        ]);
    }
}
