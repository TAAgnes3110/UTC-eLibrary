<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Validation\Rule;

class RegisterRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        // Nếu user không chọn role thì mặc định là MEMBER (member/bạn đọc legacy).
        $this->merge(['user_type' => $this->input('user_type') ?? RoleType::MEMBER->value]);

        if ($this->has('email')) {
            $email = (string) $this->input('email');
            if (str_contains($email, "\0")) {
                $this->merge(['email' => '']);
            }
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^\d{9,12}$/', 'unique:users,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'user_type' => [Rule::in([
                RoleType::STUDENT->value,
                RoleType::TEACHER->value,
                RoleType::MEMBER->value, // Legacy: giữ để tương thích dữ liệu cũ
            ])],
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
            'code.required' => 'Số CCCD/CMND không được để trống.',
            'code.regex' => 'Số CCCD/CMND phải gồm 9–12 chữ số.',
            'code.unique' => 'Số định danh này đã được đăng ký tài khoản.',
            'name.required' => 'Họ và tên không được để trống.',
            'name.max' => 'Họ và tên quá dài.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email quá dài.',
            'email.unique' => 'Email đã được đăng ký.',
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',
            'phone.max' => 'Số điện thoại không hợp lệ.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'user_type.in' => 'Loại tài khoản không hợp lệ.',
        ];
    }
}
