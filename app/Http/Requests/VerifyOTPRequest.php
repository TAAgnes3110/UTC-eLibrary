<?php

namespace App\Http\Requests;

class VerifyOTPRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'otp' => ['required'],
        ];
    }
}
