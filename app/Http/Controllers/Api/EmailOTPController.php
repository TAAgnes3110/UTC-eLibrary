<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendOTPRequest;
use App\Services\OtpService;
use Exception;
use Illuminate\Http\JsonResponse;

class EmailOTPController extends Controller
{
    public function __construct(
        private OtpService $otpService
    ) {}

    public function sendOTP(SendOTPRequest $request): JsonResponse
    {
        $email = $request->email;
        $name = $request->name ?? 'Người dùng';

        try {
            $result = $this->otpService->sendOtp($email, $name);

            if (!$result['status']) {
                $statusCode = isset($result['seconds_left']) ? 429 : 500;
                return ApiResponse::json(['status' => 'error', 'messages' => $result['message']], $statusCode);
            }

            return ApiResponse::json(['status' => 'success', 'messages' => $result['message'], 'otp' => $result['otp']], 200);
        } catch (Exception $e) {
            return ApiResponse::json(['status' => 'error', 'messages' => $e->getMessage()], 500);
        }
    }

    public function store(SendOTPRequest $request): JsonResponse
    {
        return $this->sendOTP($request);
    }

    public function checkOTP(string $email, string $otp): array
    {
        $result = $this->otpService->verifyOtp($email, $otp);
        return [
            'status' => $result['status'],
            'message' => $result['message'],
            'code' => $result['status'] ? 200 : ($result['code'] ?? 400),
        ];
    }
}
