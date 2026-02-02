<?php

namespace App\Http\Requests\AuthRequests;

use App\Enums\RoleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => $this->role ?? RoleType::GUEST->value,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255|unique:users,code',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:255|unique:users,phone',
            'role' => [Rule::in([
                RoleType::STUDENT->value,
                RoleType::TEACHER->value,
                RoleType::GUEST->value
            ])],
            'params' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Mã sinh viên hoặc CCCD không được để trống',
            'code.unique' => 'Mã sinh viên hoặc CCCD đã tồn tại',
            'name.required' => 'Tên không được để trống',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email đã tồn tại',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không đúng',
            'phone.required' => 'Số điện thoại không được để trống',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'role.in' => 'Vai trò không hợp lệ',
        ];
    }
}
