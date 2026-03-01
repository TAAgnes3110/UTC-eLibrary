<?php

namespace App\Services;

use App\Helpers\AuthHelper;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private OtpService $otpService
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

        if (!$user || !$token = JWTAuth::attempt(['email' => $user->email, 'password' => $password])) {
            return ['error' => __('messages.invalid_credentials')];
        }

        return ['user' => $user, 'token' => $token];
    }

    /**
     * @return array{message: string}|array{error: string, code?: int}
     */
    public function register(array $data): array
    {
        if ($existingUser = User::duplicate($data)->first()) {
            return ['error' => AuthHelper::duplicateUserMessage($existingUser, $data)];
        }

        if (empty($data['name'])) {
            $customer = Customer::where('code', $data['code'])->first();
            $data['name'] = $customer ? $customer->name : 'Người dùng';
        }

        Cache::put('register_' . $data['email'], $data, now()->addMinutes(15));
        $result = $this->otpService->sendOtp($data['email'], $data['name']);

        if (!$result['status']) {
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
        if (!$check['status']) {
            return ['error' => $check['message']];
        }

        $pendingUser = Cache::get('register_' . $email);
        if (!$pendingUser) {
            return ['error' => __('Phiên đăng ký đã hết hạn hoặc không tồn tại.')];
        }

        if ($existingUser = User::duplicate($pendingUser)->first()) {
            Cache::forget('register_' . $email);
            return ['error' => AuthHelper::duplicateUserMessage($existingUser, $pendingUser)];
        }

        DB::beginTransaction();
        try {
            $user = User::create($pendingUser);
            $user->email_verified_at = now();
            $user->save();
            $user->libraryCard()->create([
                'card_number' => $user->code,
                'status' => 'active',
                'is_active' => true,
                'issue_date' => now(),
            ]);
            DB::commit();
            Cache::forget('register_' . $email);
            $token = JWTAuth::fromUser($user);
            return [
                'user' => $user,
                'token' => $token,
                'message' => __('Đăng ký tài khoản thành công!'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['error' => 'Lỗi tạo tài khoản: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{message: string}|array{error: string}
     */
    public function resetPassword(string $email, string $otp, string $password): array
    {
        $check = $this->otpService->verifyOtp($email, $otp);
        if (!$check['status']) {
            return ['error' => $check['message']];
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['error' => __('Tài khoản không tồn tại.')];
        }

        $user->password = bcrypt($password);
        $user->save();
        return ['message' => __('messages.success_reset_password')];
    }
}
