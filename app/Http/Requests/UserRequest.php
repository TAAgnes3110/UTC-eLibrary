<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Validation\Rule;

class UserRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_type' => $this->input('user_type') ?? $this->input('role') ?? RoleType::MEMBER->value,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('user');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('users', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($id)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8'],
            'user_type' => ['required', Rule::in(array_column(RoleType::cases(), 'value'))],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'cohort' => ['nullable', 'string', 'max:20'],
            'card_number' => ['nullable', 'string', 'max:50'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Mã không được để trống'),
            'code.unique' => __('Mã số (MSV/CCCD) đã tồn tại trong hệ thống.'),
            'name.required' => __('Tên không được để trống'),
            'email.required' => __('Email không được để trống'),
            'email.unique' => __('Email đã tồn tại trong hệ thống.'),
            'phone.unique' => __('Số điện thoại đã tồn tại trong hệ thống.'),
            'password.required' => __('Mật khẩu không được để trống'),
            'password.min' => __('Mật khẩu tối thiểu 8 ký tự'),
        ];
    }
}
