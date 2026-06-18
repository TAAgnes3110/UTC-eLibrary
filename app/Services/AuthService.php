<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Helpers\AuthHelper;
use App\Models\Customer;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardManagementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private OtpService $otpService,
        private LibraryCardManagementService $libraryCardManagement
    ) {}

    /**
     * @return array{user: User, token: string}|array{error: string}
     */
    public function login(string $loginField, string $password): array
    {
        $user = User::query()
            ->where('email', $loginField)
            ->orWhere('code', $loginField)
            ->orWhere('phone', $loginField)
            ->orWhereHas('libraryCard', fn ($q) => $q->where('card_number', $loginField))
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return ['error' => __('messages.invalid_credentials')];
        }

        try {
            $token = JWTAuth::fromUser($user);
        } catch (\Throwable $e) {
            Log::error('Không tạo được JWT khi đăng nhập.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_type' => $user->user_type instanceof RoleType ? $user->user_type->value : $user->user_type,
                'error' => $e->getMessage(),
            ]);

            return ['error' => __('Không thể hoàn tất đăng nhập. Vui lòng liên hệ quản trị viên.'), 'code' => 500];
        }

        return ['user' => $user, 'token' => $token];
    }

    /**
     * @return array{message: string}|array{error: string, code?: int}
     */
    public function register(array $data): array
    {
        // Luôn khởi tạo tài khoản ở vai trò MEMBER; xác nhận STUDENT/TEACHER xử lý qua luồng duyệt hồ sơ.
        $data['user_type'] = RoleType::MEMBER->value;

        if ($existingUser = User::duplicate($data)->first()) {
            return ['error' => AuthHelper::duplicateUserMessage($existingUser, $data)];
        }

        if (empty($data['name'])) {
            $customer = Customer::where('code', $data['code'])->first();
            $data['name'] = $customer ? $customer->name : 'Người dùng';
        }

        Cache::put('register_'.$data['email'], $data, now()->addMinutes(15));
        $result = $this->otpService->sendOtp($data['email'], $data['name']);

        if (! $result['status']) {
            $code = isset($result['seconds_left']) ? 429 : 500;

            return ['error' => $result['message'], 'code' => $code];
        }

        return ['message' => $result['message']];
    }

    /**
     * @return array{user: User, token: string, message: string}|array{error: string}
     */
    public function verifyRegister(string $email, string $otp): array
    {
        $check = $this->otpService->verifyOtp($email, $otp);
        if (! $check['status']) {
            return ['error' => $check['message']];
        }

        $pendingUser = Cache::get('register_'.$email);
        if (! $pendingUser) {
            return ['error' => __('Phiên đăng ký đã hết hạn hoặc không tồn tại.')];
        }

        if ($existingUser = User::duplicate($pendingUser)->first()) {
            Cache::forget('register_'.$email);

            return ['error' => AuthHelper::duplicateUserMessage($existingUser, $pendingUser)];
        }

        DB::beginTransaction();
        try {
            $user = User::create($pendingUser);
            $user->email_verified_at = now();
            $user->save();
            $this->libraryCardManagement->linkOrphanGuestCardToNewUser($user);
            DB::commit();
            Cache::forget('register_'.$email);
            $token = JWTAuth::fromUser($user);

            return [
                'user' => $user,
                'token' => $token,
                'message' => __('Đăng ký tài khoản thành công!'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['error' => 'Lỗi tạo tài khoản: '.$e->getMessage()];
        }
    }

    /**
     * @return array{message: string}|array{error: string}
     */
    public function resetPassword(string $email, string $otp, string $password): array
    {
        $check = $this->otpService->verifyOtp($email, $otp);
        if (! $check['status']) {
            return ['error' => $check['message']];
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return ['error' => __('Tài khoản không tồn tại.')];
        }

        $user->password = bcrypt($password);
        $user->save();

        return ['message' => __('messages.success_reset_password')];
    }
}
