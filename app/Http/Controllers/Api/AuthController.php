<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Chỉ điều hướng: gọi AuthService, trả JSON (Resource / ApiResponse).
 */
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('login'),
            $request->input('password')
        );

        if (isset($result['error'])) {
            return ApiResponse::json(['status' => 'error', 'messages' => $result['error']], 401);
        }

        Auth::guard('web')->login($result['user'], $request->boolean('remember'));
        return ApiResponse::json([
            'status' => 'success',
            'messages' => __('messages.success_login'),
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ], 200);
    }

    /**
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        if (isset($result['error'])) {
            $code = $result['code'] ?? 400;
            return ApiResponse::json(['status' => 'error', 'messages' => $result['error']], $code);
        }

        return ApiResponse::json(['status' => 'success', 'messages' => $result['message']], 200);
    }

    /**
     * @param VerifyOTPRequest $request
     * @return JsonResponse
     */
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->authService->resetPassword(
            $request->email,
            $request->otp,
            $request->password
        );

        if (isset($result['error'])) {
            return ApiResponse::json(['status' => 'error', 'messages' => $result['error']], 400);
        }

        return ApiResponse::json(['status' => 'success', 'messages' => $result['message']], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        return ApiResponse::json(new UserResource($request->user()));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();
        if (!$bearer) {
            return ApiResponse::json(['status' => 'error', 'messages' => __('Vui lòng gửi token.')], 401);
        }

        try {
            $token = JWTAuth::setToken($bearer)->refresh();
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
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
