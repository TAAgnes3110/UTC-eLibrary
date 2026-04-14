<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleType;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
        private UserService $userService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $payload = $this->profileService->getProfilePayload($request->user());

        return ApiResponse::success($payload);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : ($user->user_type ?? null);
        $isStaff = $roleValue && in_array($roleValue, RoleType::staffRoles(), true);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,'.$user->id],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];

        if ($isStaff) {
            $rules['code'] = ['required', 'string', 'regex:/^\d{9,12}$/', 'unique:users,code,'.$user->id];
        }

        $validated = $request->validate($rules);

        $data = $this->profileService->updateProfile($user, $validated);

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

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $file = $request->file('avatar');
        if (! $file) {
            return ApiResponse::error('Vui lòng chọn một file ảnh hợp lệ.', 422);
        }

        try {
            $this->userService->updateAvatar($user, $file);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(
            $this->profileService->getProfilePayload($user->fresh()),
            'Đã cập nhật ảnh đại diện.'
        );
    }
}
