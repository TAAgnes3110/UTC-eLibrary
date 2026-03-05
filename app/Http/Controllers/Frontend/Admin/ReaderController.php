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
        $userResponse = app(UserController::class)->readers();
        $userData = $this->backendData($userResponse);
        $raw = $userData['data'] ?? $userData['readers'] ?? [];
        $raw = is_array($raw) ? $raw : [];

        $readers = collect($raw)->map(function ($u) {
            $lc = $u['library_card'] ?? null;
            $faculty = $u['faculty'] ?? null;
            $dept = $u['department'] ?? null;
            return [
                'id' => $u['id'] ?? null,
                'name' => $u['name'] ?? '',
                'code' => $u['code'] ?? '',
                'email' => $u['email'] ?? '',
                'phone' => $u['phone'] ?? '',
                'type' => $u['params']['reader_type'] ?? (($u['user_type'] ?? '') === 'MEMBER' ? 'student' : (($u['user_type'] ?? '') === 'GUEST' ? 'other' : 'student')),
                'status' => $lc['status'] ?? ($u['is_active'] ? 'active' : 'inactive'),
                'faculty_id' => $u['faculty_id'] ?? null,
                'department_id' => $u['department_id'] ?? null,
                'cohort' => $u['cohort'] ?? '',
                'card_number' => $lc['card_number'] ?? $u['code'] ?? '',
                'issue_date' => $lc['issue_date'] ?? null,
                'expiry_date' => $lc['expiry_date'] ?? null,
                'faculty' => is_array($faculty) ? ($faculty['name'] ?? '') : (is_string($faculty) ? $faculty : ''),
                'class' => is_array($dept) ? ($dept['name'] ?? '') : (is_string($dept) ? $dept : ''),
            ];
        })->values()->all();

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
