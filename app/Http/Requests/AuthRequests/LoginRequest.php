<?php

namespace App\Http\Requests\AuthRequests;

use App\Http\Requests\BaseRequest;

/**
 * Form request validate đăng nhập (login + password).
 */
class LoginRequest extends BaseRequest
{
    /**
     * Rule validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => 'required',
            'password' => 'required'
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
            'login.required' => 'Login is required.',
            'password.required' => 'Password is required.',
        ];
    }
}
