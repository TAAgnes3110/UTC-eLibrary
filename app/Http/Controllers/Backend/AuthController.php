<?php

namespace App\Http\Controllers\Backend;

use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\EmailOTPController;
use App\Http\Requests\AuthRequests\ForgotPasswordRequest;
use App\Http\Requests\AuthRequests\LoginRequest;
use App\Http\Requests\AuthRequests\RegisterRequest;
use App\Http\Requests\OtpRequests\VerifyOTPRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return JsonResponse
     * @todo Đăng nhập
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $loginField = $request->input('login');
        $password = $request->input('password');

        $user = User::query()
            ->where('email', $loginField)
            ->orWhere('code', $loginField)
            ->orWhere('phone', $loginField)
            ->orWhereHas('libraryCard', function ($query) use ($loginField) {
                $query->where('card_number', $loginField);
            })
            ->first();

        if ($user) {
            $credentials = [
                'email' => $user->email,
                'password' => $password
            ];

            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'messages' => __('messages.invalid_credentials')
                ], 401);
            }

            // Start session for web guard (Inertia)
            auth('web')->login($user, $request->boolean('remember'));

            return $this->jsonResponse([
                'status' => 'success',
                'messages' => __('messages.success_login'),
                'token' => $token,
                'user' => new UserResource($user)
            ], 200);
        } else {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('Tài khoản hoặc mật khẩu không đúng')
            ], 401);
        }
    }

    /**
     * @param RegisterRequest $request
     * @return JsonResponse
     * @todo Đăng ký
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            Cache::put('register_' . $data['email'], $data, 600);
            app(EmailOTPController::class)->sendOtp($data['email'], $data['name']);

            return $this->jsonResponse([
                'status' => 'success',
                'messages' => __('Vui lòng kiểm tra email để lấy mã OTP xác nhận tài khoản.'),
                'email' => $data['email']
            ], 200);
        } catch (Exception $e) {
            Cache::forget('register_' . $data['email']);
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => 'Lỗi gửi OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @param VerifyOTPRequest $request
     * @return JsonResponse
     * @todo Xác nhận OTP
     */
    public function verifyRegister(VerifyOTPRequest $request): JsonResponse
    {
        $otpCheck = app(EmailOTPController::class)->checkOTP($request->email, $request->otp);

        if (!$otpCheck['status']) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => $otpCheck['message']
            ], $otpCheck['code']);
        }

        $pendingUser = Cache::get('register_' . $request->email);

        if ($pendingUser) {
            DB::beginTransaction();
            try {
                $user = User::create($pendingUser);
                $user->email_verified_at = now();
                $user->save();

                DB::commit();
                Cache::forget('register_' . $request->email);

                $token = JWTAuth::fromUser($user);

                return $this->jsonResponse([
                    'status' => 'success',
                    'messages' => __('Đăng ký tài khoản thành công!'),
                    'token' => $token,
                    'user' => $user
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                return $this->jsonResponse([
                    'status' => 'error',
                    'messages' => 'Lỗi tạo tài khoản: ' . $e->getMessage()
                ], 500);
            }
        }

        return $this->jsonResponse([
            'status' => 'error',
            'messages' => __('Phiên đăng ký đã hết hạn hoặc không tồn tại.')
        ], 404);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('messages.user_not_found')
            ], 404);
        }

        $user->password = bcrypt($data['password']);
        $user->save();

        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_reset_password')
        ], 200);
    }

    /**
     * @return JsonResponse
     * @todo Đăng xuất
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_logout')
        ], 200);
    }

    public function user(Request $request): JsonResponse
    {
        return $this->jsonResponse(new UserResource($request->user()));
    }
}
