<?php

namespace App\Http\Requests\AuthRequests;

use App\Enums\RoleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request validate đăng ký tài khoản (code, name, email, password, ...).
 */
class RegisterRequest extends FormRequest
{
    /**
     * Chuẩn hóa user_type mặc định MEMBER.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_type' => RoleType::MEMBER->value,
        ]);
    }

    /**
     * Rule validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255|unique:users,code',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'user_type' => [Rule::in([RoleType::GUEST->value, RoleType::MEMBER->value])],

            'organization' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'address' => 'nullable|string|max:1000',

            'params' => 'nullable|array',
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
            'code.required' => 'Mã sinh viên hoặc CCCD không được để trống',
            'code.unique' => 'Mã số đã tồn tại trong hệ thống',
            'code.exists' => 'Mã sinh viên không tồn tại trong danh sách bạn đọc',
            'name.required' => 'Tên không được để trống',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email đã tồn tại',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không đúng',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'user_type.enum' => 'Loại người dùng không hợp lệ',
        ];
    }
}
