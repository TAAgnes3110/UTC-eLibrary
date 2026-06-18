<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\Notifications\StaffWorkQueueNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private readonly StaffWorkQueueNotificationService $staffWorkQueueNotificationService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $remember = $request->boolean('remember');

        if ($remember) {
            JWTAuth::factory()->setTTL(config('jwt.remember_ttl'));
        } else {
            JWTAuth::factory()->setTTL(config('jwt.ttl'));
        }

        $result = $this->authService->login(
            $request->input('login'),
            $request->input('password')
        );

        if (isset($result['error'])) {
            $code = (int) ($result['code'] ?? 401);

            return ApiResponse::json(['status' => 'error', 'messages' => $result['error']], $code);
        }

        Auth::guard('web')->login($result['user'], $remember);

        $user = $result['user'];
        $staffWorkQueue = null;
        try {
            $staffWorkQueue = $this->staffWorkQueueNotificationService->syncForUser($user);
        } catch (\Throwable $e) {
            // Không để lỗi đồng bộ hàng chờ staff làm hỏng luồng đăng nhập.
            Log::warning('Bỏ qua lỗi đồng bộ staff work queue khi đăng nhập.', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $payload = [
                'status' => 'success',
                'messages' => __('messages.success_login'),
                'token' => $result['token'],
                'user' => new UserResource($user),
            ];
            if ($staffWorkQueue !== null) {
                $payload['staff_work_queue'] = $staffWorkQueue;
            }

            return ApiResponse::json($payload, 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi serialize user khi đăng nhập.', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::json([
                'status' => 'error',
                'messages' => __('Không thể hoàn tất đăng nhập. Vui lòng liên hệ quản trị viên.'),
            ], 500);
        }
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        if (isset($result['error'])) {
            $code = $result['code'] ?? 400;

            return ApiResponse::json(['status' => 'error', 'messages' => $result['error']], $code);
        }

        return ApiResponse::json(['status' => 'success', 'messages' => $result['message']], 200);
    }

    public function verifyRegister(VerifyOTPRequest $request): JsonResponse
    {
        $result = $this->authService->verifyRegister($request->email, $request->otp);

        if (isset($result['error'])) {
            return ApiResponse::json(['status' => 'error', 'messages' => $result['error']], 400);
        }

        return ApiResponse::json([
            'status' => 'success',
            'messages' => $result['message'],
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->authService->resetPassword(
            $validated['email'],
            $validated['otp'],
            $validated['password']
        );

        if (isset($result['error'])) {
            return ApiResponse::json(['status' => 'error', 'messages' => $result['error']], 400);
        }

        return ApiResponse::json(['status' => 'success', 'messages' => $result['message']], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
        }

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ApiResponse::json([
            'status' => 'success',
            'messages' => __('messages.success_logout'),
        ], 200);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $staffWorkQueue = $this->staffWorkQueueNotificationService->syncForUser($user);

        $data = (new UserResource($user))->resolve();
        if ($staffWorkQueue !== null) {
            $data['staff_work_queue'] = $staffWorkQueue;
        }

        return ApiResponse::json($data);
    }

    /**
     * Cấp JWT mới khi SPA đã có session web (Inertia) nhưng localStorage chưa có token.
     */
    public function sessionToken(Request $request): JsonResponse
    {
        global $currentPerson;

        $user = $currentPerson ?? Auth::guard('web')->user();
        if (! $user) {
            return ApiResponse::error(__('Bạn cần đăng nhập để tiếp tục.'), 401);
        }

        $token = JWTAuth::fromUser($user);
        $ttl = (int) config('jwt.ttl', 60);

        return ApiResponse::json([
            'status' => 'success',
            'messages' => __('Cấp token thành công.'),
            'token' => $token,
            'expires_in' => $ttl * 60,
        ], 200);
    }

    public function refresh(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return ApiResponse::json(['status' => 'error', 'messages' => __('Vui lòng gửi token.')], 401);
        }

        try {
            $token = JWTAuth::setToken($bearer)->refresh();
        } catch (TokenExpiredException $e) {
            return ApiResponse::json([
                'status' => 'error',
                'messages' => __('Token đã hết hạn, vui lòng đăng nhập lại.'),
            ], 401);
        } catch (\Exception $e) {
            return ApiResponse::json(['status' => 'error', 'messages' => __('Token không hợp lệ.')], 401);
        }

        $ttl = (int) config('jwt.ttl', 60);

        return ApiResponse::json([
            'status' => 'success',
            'messages' => __('Cấp lại token thành công.'),
            'token' => $token,
            'expires_in' => $ttl * 60,
        ], 200);
    }
}
