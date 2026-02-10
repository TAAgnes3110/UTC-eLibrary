<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\OtpRequests\SendOTPRequest;
use App\Models\EmailOtp;
use App\Services\OtpService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Helpers\CurrentUser;

class EmailOTPController extends Controller
{
    /**
     * @param SendOTPRequest $request
     * @return JsonResponse
     * @todo Gửi mã OTP
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
                return $this->jsonResponse(['status' => 'error', 'messages' => $result['message']], $statusCode);
            }

            return $this->jsonResponse(['status' => 'success', 'messages' => $result['message'], 'otp' => $result['otp']], 200);
        } catch (Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }

    /**
     * @param SendOTPRequest $request
     * @return JsonResponse
     * @todo Lưu mã OTP
     */
    public function store(SendOTPRequest $request): JsonResponse
    {
        return $this->sendOTP($request);
    }

    /**
     * @param string $email
     * @param string $otp
     * @return array
     * @todo Kiểm tra mã OTP
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
