<?php

namespace App\Http\Requests\OtpRequests;

use App\Http\Requests\BaseRequest;

/**
 * Form request validate xác thực OTP (email + otp).
 */
class VerifyOTPRequest extends BaseRequest
{
    /**
     * Rule validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'otp' => 'required',
        ];
    }

    /**
     * Thông báo lỗi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email là bắt buộc',
            'otp.required' => 'OTP là bắt buộc',
        ];
    }
}
