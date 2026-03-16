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
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('users', 'code')->ignore($id),
            ],
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'email' => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($id),
            ],
            'password' => [
                $isUpdate ? 'sometimes' : 'required',
                'nullable',
                'string',
                'min:8',
            ],
            'user_type' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in(array_column(RoleType::cases(), 'value')),
            ],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female,other'],
            'faculty_id' => ['sometimes', 'nullable', 'integer', 'min:1', 'exists:faculties,id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'min:1', 'exists:departments,id'],
            'cohort' => ['sometimes', 'nullable', 'string', 'max:20'],
            'card_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'issue_date' => ['sometimes', 'nullable', 'date'],
            'expiry_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:issue_date'],
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
