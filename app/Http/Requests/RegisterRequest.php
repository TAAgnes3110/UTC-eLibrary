<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Validation\Rule;

class RegisterRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['user_type' => $this->input('user_type') ?? RoleType::MEMBER->value]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:users,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'user_type' => [Rule::in([RoleType::GUEST->value, RoleType::MEMBER->value])],
            'organization' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:1000'],
            'params' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Mã không được để trống',
            'code.unique' => 'Mã đã tồn tại',
            'email.unique' => 'Email đã tồn tại',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không đúng',
        ];
    }
}
