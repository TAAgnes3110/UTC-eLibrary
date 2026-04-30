<?php

namespace App\Http\Requests;

use App\Enums\RoleType;

class StoreUserProfileUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : ($user->user_type ?? null);

        return ! ($roleValue && in_array($roleValue, RoleType::staffRoles(), true));
    }

    public function rules(): array
    {
        return [
            'requested_code' => ['nullable', 'string', 'regex:/^\d{9,12}$/'],
            'requested_user_type' => ['nullable', 'string', 'in:STUDENT,TEACHER'],
            'requested_faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'requested_period_id' => ['nullable', 'integer', 'exists:periods,id'],
            'requested_class_code' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_code.regex' => 'Mã định danh phải gồm 9-12 chữ số.',
            'requested_user_type.in' => 'Loại xác nhận chỉ nhận Sinh viên hoặc Giáo viên.',
            'proof_image.required' => 'Bạn cần tải ảnh minh chứng để gửi yêu cầu.',
            'proof_image.image' => 'File minh chứng phải là ảnh.',
            'proof_image.mimes' => 'Ảnh minh chứng chỉ nhận: jpg, jpeg, png, webp.',
            'proof_image.max' => 'Ảnh minh chứng không được vượt quá 5MB.',
        ];
    }
}

