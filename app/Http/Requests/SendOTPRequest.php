<?php

namespace App\Http\Requests;

class SendOTPRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255']];
    }
}
