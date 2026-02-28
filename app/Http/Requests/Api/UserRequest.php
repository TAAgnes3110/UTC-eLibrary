<?php

namespace App\Http\Requests\Api;

use App\Enums\RoleType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

/**
 * Form request validate tạo/cập nhật người dùng (API).
 */
class UserRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $userType = $this->input('user_type') ?? $this->input('role');
        $this->merge([
            'user_type' => $userType ?? RoleType::MEMBER->value,
        ]);
    }

    public function rules(): array
    {
        $userId = $this->route('user');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

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
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8'],
            'user_type' => ['required', Rule::in(array_column(RoleType::cases(), 'value'))],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'card_number' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
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
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'user_type.in' => 'Vai trò không hợp lệ',
        ];
    }
}
