<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chỉ điều hướng: gọi ProfileService, trả ApiResponse.
 */
class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $payload = $this->profileService->getProfilePayload($request->user());
        return ApiResponse::success($payload);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $request->user()->id],
            'password' => ['nullable', 'string', 'min:8'],
            'gender' => ['nullable', 'string', 'in:male,female,other,Nam,Nữ,Khác'],
        ]);

        $data = $this->profileService->updateProfile($request->user(), $validated);
        return ApiResponse::success($data, 'Đã cập nhật thông tin cá nhân.');
    }
}
