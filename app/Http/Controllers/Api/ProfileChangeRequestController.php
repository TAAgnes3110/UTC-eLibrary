<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ProfileChangeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chỉ điều hướng: gọi ProfileChangeRequestService, trả ApiResponse.
 */
class ProfileChangeRequestController extends Controller
{
    public function __construct(
        private ProfileChangeRequestService $profileChangeRequestService
    ) {}

    public function pageData(Request $request): JsonResponse
    {
        $payload = $this->profileChangeRequestService->pageData($request->user());
        return ApiResponse::success($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:code,faculty_id,department_id,cohort'],
            'value_new' => ['required', 'string', 'max:255'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $user = $request->user();
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('profile_change_proofs', 'public');
        }

        $result = $this->profileChangeRequestService->store(
            $user,
            $validated['field'],
            $validated['value_new'],
            $proofPath
        );

        if (!$result['success']) {
            return ApiResponse::error(
                $result['message'],
                $result['code'],
                $result['errors'] ?? null
            );
        }

        return ApiResponse::success(null, 'Đã gửi yêu cầu. Quản trị viên hoặc thủ thư sẽ xem xét và duyệt.', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status', 'pending');
        $payload = $this->profileChangeRequestService->index($status);
        return ApiResponse::success($payload);
    }

    public function approve(int $id): JsonResponse
    {
        if (!$this->profileChangeRequestService->approve($id, request()->user()->id)) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, 'Đã duyệt yêu cầu.');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        if (!$this->profileChangeRequestService->reject($id, $request->user()->id, $request->input('admin_note'))) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, 'Đã từ chối yêu cầu.');
    }
}
