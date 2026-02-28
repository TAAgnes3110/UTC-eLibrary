<?php

namespace App\Http\Requests\AuthRequests;

use App\Http\Requests\BaseRequest;

/**
 * Form request validate quên mật khẩu / đặt lại mật khẩu (email, password).
 */
class ForgotPasswordRequest extends BaseRequest
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
            'password' => 'required|string|min:8|confirmed',
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
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không hợp lệ',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không đúng',
        ];
    }
}
