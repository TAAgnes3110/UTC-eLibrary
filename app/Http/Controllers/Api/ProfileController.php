<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,'.$request->user()->id],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = $this->profileService->updateProfile($request->user(), $validated);

        return ApiResponse::success($data, 'Đã cập nhật thông tin cá nhân.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return ApiResponse::error('Mật khẩu hiện tại không chính xác.', 422, [
                'current_password' => ['Mật khẩu hiện tại không chính xác.'],
            ]);
        }

        $this->profileService->updatePassword($request->user(), $validated['password']);

        return ApiResponse::success(null, 'Đã cập nhật mật khẩu.');
    }
}
