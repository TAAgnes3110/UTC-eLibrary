<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\OtpRequests\SendOTPRequest;
use App\Services\OtpService;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Controller gửi OTP qua email (đăng ký, quên mật khẩu).
 *
 * @todo Giới hạn số lần gửi OTP theo email/ngày.
 */
class EmailOTPController extends Controller
{
    /**
     * Gửi mã OTP đến email (có giới hạn 90 giây giữa hai lần gửi).
     *
     * @param SendOTPRequest $request
     * @return JsonResponse
     */
    public function sendOTP(SendOTPRequest $request): JsonResponse
    {
        $email = $request->email;
        $name = $request->name ?? 'Người dùng';

        try {
            $otpService = app(OtpService::class);
            $result = $otpService->sendOtp($email, $name);

            if (!$result['status']) {
                $statusCode = isset($result['seconds_left']) ? 429 : 500;
                return ApiResponse::json(['status' => 'error', 'messages' => $result['message']], $statusCode);
            }

            return ApiResponse::json(['status' => 'success', 'messages' => $result['message'], 'otp' => $result['otp']], 200);
        } catch (Exception $e) {
            return ApiResponse::json(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }

    /**
     * Alias của sendOTP: lưu / gửi mã OTP (dùng cho route store).
     *
     * @param SendOTPRequest $request
     * @return JsonResponse
     */
    public function store(SendOTPRequest $request): JsonResponse
    {
        return $this->sendOTP($request);
    }

    /**
     * Kiểm tra mã OTP có đúng và còn hiệu lực không (dùng nội bộ, không phải API).
     *
     * @param string $email
     * @param string $otp
     * @return array{status: bool, message: string, code: int}
     */
    public function checkOTP(string $email, string $otp): array
    {
        $otpService = app(OtpService::class);
        $result = $otpService->verifyOtp($email, $otp);

        return [
            'status' => $result['status'],
            'message' => $result['message'],
            'code' => $result['status'] ? 200 : ($result['code'] ?? 400)
        ];
    }
}
