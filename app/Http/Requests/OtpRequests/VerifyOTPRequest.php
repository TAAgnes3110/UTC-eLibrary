<?php

namespace App\Http\Requests\OtpRequests;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOTPRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'otp' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email là bắt buộc',
            'otp.required' => 'OTP là bắt buộc',
        ];
    }
}
