<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\StaffWorkQueueSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffWorkQueueController extends Controller
{
    public function __construct(
        private readonly StaffWorkQueueSummaryService $staffWorkQueueSummaryService
    ) {}

    /**
     * GET /api/v1/me/staff-work-queue — chỉ staff; dùng sau đăng nhập (OAuth) hoặc làm mới số liệu.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $summary = $this->staffWorkQueueSummaryService->summaryForUser($user);
        if ($summary === null) {
            return ApiResponse::error(__('Chỉ tài khoản nội bộ mới xem được thông tin này.'), 403);
        }

        return ApiResponse::success($summary);
    }
}
