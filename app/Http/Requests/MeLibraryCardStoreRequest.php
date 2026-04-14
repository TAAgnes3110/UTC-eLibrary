<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Validation\Rule;
class MeLibraryCardStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        $role = $this->user()?->user_type;

        $rules = [
            'code' => [
                Rule::requiredIf(fn () => ! filled(trim((string) ($this->user()?->code ?? '')))),
                'nullable',
                'string',
                'max:255',
            ],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'photo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo_file' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'params' => ['prohibited'],
            'paid_at_counter' => ['sometimes', 'boolean'],
            'payment_amount' => ['required_if:paid_at_counter,true', 'nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'receipt_number' => ['nullable', 'string', 'max:50'],
        ];

        if ($role === RoleType::STUDENT) {
            $rules['faculty_id'] = ['required', 'integer', 'exists:faculties,id'];
            $rules['period_id'] = ['required', 'integer', 'exists:periods,id'];
            $rules['class_code'] = ['required', 'string', 'max:80'];
        } elseif ($role === RoleType::TEACHER) {
            $rules['faculty_id'] = ['required', 'integer', 'exists:faculties,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Mã định danh không được để trống'),
            'faculty_id.required' => __('Khoa không được để trống'),
            'period_id.required' => __('Niên khóa không được để trống'),
            'class_code.required' => __('Lớp không được để trống'),
            'photo_file.image' => __('Ảnh 3x4 phải là file ảnh hợp lệ.'),
            'photo_file.mimes' => __('Ảnh 3x4 chỉ nhận định dạng jpg, jpeg, png, webp.'),
            'photo_file.max' => __('Ảnh 3x4 không được vượt quá 5MB.'),
            'payment_amount.required_if' => __('Vui lòng nhập số tiền đã thu tại quầy.'),
        ];
    }
}
