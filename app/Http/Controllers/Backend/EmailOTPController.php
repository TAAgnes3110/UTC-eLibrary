<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\OtpRequests\SendOTPRequest;
use App\Mail\SendOTP;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Helpers\CurrentUser;

class EmailOTPController extends Controller
{
    /**
     * @param string $email
     * @param string $name
     * @return void
     */
    public function sendOtp($email, $name)
    {
        $otp = rand(100000, 999999);
        $data = [
            'otp' => $otp,
            'name' => $name
        ];

        Cache::put('otp_' . $email, $otp, 300);

        Mail::to($email)->send(new SendOTP($data));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $user = \App\Models\User::where('email', $email)->first();
        if ($user) {
            $this->sendOtp($email, $user->name);
            return $this->jsonResponse(['status' => 'success', 'messages' => 'OTP sent successfully'], 200);
        }

        return $this->jsonResponse(['status' => 'error', 'messages' => 'User not found'], 404);
    }

    public function checkOTP($email, $otp)
    {
        $cachedOtp = Cache::get('otp_' . $email);

        if (!$cachedOtp) {
            return ['status' => false, 'message' => 'OTP expired or not found', 'code' => 400];
        }

        if ($cachedOtp != $otp) {
            return ['status' => false, 'message' => 'Invalid OTP', 'code' => 400];
        }

        Cache::forget('otp_' . $email);
        return ['status' => true, 'message' => 'OTP Verified', 'code' => 200];
    }
}
