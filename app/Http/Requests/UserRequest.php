<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Validation\Rule;

class UserRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => $this->role ?? RoleType::GUEST->value,
        ]);
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'code')->ignore($userId),
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in(array_column(RoleType::cases(), 'value'))],
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
            'phone.required' => 'Số điện thoại không được để trống',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'role.in' => 'Vai trò không hợp lệ',
        ];
    }
}
