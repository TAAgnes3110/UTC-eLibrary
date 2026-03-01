<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Chỉ render trang và trả file export. Dữ liệu lấy từ Backend API. */
class ReaderController extends Controller
{
    use DecodesBackendResponse;

    public function index(Request $request): Response
    {
        $userResponse = app(UserController::class)->readersPageData();
        $userData = $this->backendData($userResponse);
        $readers = $userData['data'] ?? $userData['readers'] ?? [];

        $masterData = $this->backendData(app(\App\Http\Controllers\Api\MasterDataController::class)->index($request));

        return Inertia::render('Admin/Readers/Index', [
            'readers' => $readers,
            'faculties' => $masterData['faculties'] ?? [],
            'departments' => $masterData['departments'] ?? [],
            'cohorts' => $masterData['cohorts'] ?? [],
        ]);
    }

    public function export(): BinaryFileResponse
    {
        return app(UserController::class)->exportReaders();
    }
}
