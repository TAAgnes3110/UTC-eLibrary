<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequests\ForgotPasswordRequest;
use App\Http\Requests\AuthRequests\LoginRequest;
use App\Http\Requests\AuthRequests\RegisterRequest;
use App\Http\Requests\OtpRequests\VerifyOTPRequest;
use App\Http\Resources\UserResource;
use App\Models\Customer;
use App\Models\User;
use App\Services\OtpService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Đăng nhập (email/mã/SĐT/số thẻ + mật khẩu), trả về token và thông tin user.
     *
     * @param LoginRequest $request
     * @return JsonResponse
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
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('messages.invalid_credentials')
            ], 401);
        }
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
        Auth::guard('web')->login($user, $request->boolean('remember'));
        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_login'),
            'token' => $token,
            'user' => new UserResource($user)
        ], 200);
    }

    /**
     * Đăng ký tài khoản: gửi OTP qua email, lưu dữ liệu tạm vào cache.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($existingUser = User::duplicate($data)->first()) {
            return app(UserController::class)->existingUserResponse($existingUser, $data);
        }
        try {
            if (empty($data['name'])) {
                $customer = Customer::where('code', $data['code'])->first();
                $data['name'] = $customer ? $customer->name : 'Người dùng';
            }
            Cache::put('register_' . $data['email'], $data, now()->addMinutes(15));
            $otpService = app(OtpService::class);
            $result = $otpService->sendOtp($data['email'], $data['name']);
            if (!$result['status']) {
                $statusCode = isset($result['seconds_left']) ? 429 : 500;
                return $this->jsonResponse(['status' => 'error', 'messages' => $result['message']], $statusCode);
            }
            return $this->jsonResponse([
                'status' => 'success',
                'messages' => $result['message']
            ], 200);
        } catch (Exception $e) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác nhận mã OTP và hoàn tất tạo tài khoản (kèm thẻ thư viện mặc định).
     *
     * @param VerifyOTPRequest $request
     * @return JsonResponse
     */
    public function verifyRegister(VerifyOTPRequest $request): JsonResponse
    {
        $otpService = app(OtpService::class);
        $otpCheck = $otpService->verifyOtp($request->email, $request->otp);

        if (!$otpCheck['status']) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => $otpCheck['message']
            ], 400);
        }
        $pendingUser = Cache::get('register_' . $request->email);
        if (!$pendingUser) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('Phiên đăng ký đã hết hạn hoặc không tồn tại.')
            ], 404);
        }
        if ($existingUser = User::duplicate($pendingUser)->first()) {
            Cache::forget('register_' . $request->email);
            return app(UserController::class)->existingUserResponse($existingUser, $pendingUser);
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

    /**
     * Đặt lại mật khẩu (sau khi xác thực OTP).
     *
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

        $otpService = app(OtpService::class);
        $otpCheck = $otpService->verifyOtp($request->email, $request->otp);

        if (!$otpCheck['status']) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => $otpCheck['message']
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('Tài khoản không tồn tại.')
            ], 404);
        }
        $user->password = bcrypt($request->password);
        $user->save();
        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_reset_password')
        ], 200);
    }

    /**
     * Đăng xuất: vô hiệu hóa token JWT và hủy phiên web.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token đã hết hạn hoặc không tồn tại — bỏ qua
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_logout')
        ], 200);
    }

    /**
     * Lấy thông tin user đang đăng nhập.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        return $this->jsonResponse(new UserResource($request->user()));
    }
}
