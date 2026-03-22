<?php

namespace App\Http\Requests;

class SendOTPRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255']];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email quá dài.',
        ];
    }
}
