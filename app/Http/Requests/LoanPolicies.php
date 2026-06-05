<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use App\Models\LoanPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class LoanPolicies extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $codeUnique = Rule::unique('loan_policies', 'code');
        if ($isUpdate) {
            $routePolicy = $this->route('loan_policy');
            if ($routePolicy instanceof LoanPolicy) {
                $codeUnique->ignore($routePolicy);
            } elseif ($routePolicy !== null && $routePolicy !== '') {
                $codeUnique->ignore($routePolicy);
            }
        }

        return [
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                $codeUnique,
            ],
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'user_type' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::enum(RoleType::class),
            ],
            'max_books' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'min:0',
            ],
            'max_days' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'min:0',
            ],
            'max_renewals' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'min:0',
            ],
            'overdue_fine_per_day' => [
                $isUpdate ? 'sometimes' : 'required',
                'decimal:0,2',
                'min:0',
            ],
            'allow_home' => [
                'sometimes',
                'boolean',
            ],
            'allow_onsite' => [
                'sometimes',
                'boolean',
            ],
            'params' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'params.max_textbooks' => ['sometimes', 'integer', 'min:0'],
            'params.max_reference' => ['sometimes', 'integer', 'min:0'],
            'params.damage_fine_percent' => ['sometimes', 'numeric', 'min:0', 'max:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Mã chính sách không được để trống'),
            'code.string' => __('Mã chính sách phải là chuỗi'),
            'code.max' => __('Mã chính sách không được vượt quá 50 ký tự'),
            'code.unique' => __('Mã chính sách đã tồn tại'),

            'name.required' => __('Tên chính sách không được để trống'),
            'name.string' => __('Tên chính sách phải là chuỗi'),
            'name.max' => __('Tên chính sách không được vượt quá 255 ký tự'),

            'user_type.string' => __('Đối tượng áp dụng phải là chuỗi'),
            'user_type.max' => __('Đối tượng áp dụng không được vượt quá 50 ký tự'),
            'user_type.enum' => __('Đối tượng áp dụng không hợp lệ'),

            'max_books.required' => __('Số đầu sách mượn tối đa không được để trống'),
            'max_books.integer' => __('Số đầu sách mượn tối đa phải là số nguyên'),
            'max_books.min' => __('Số đầu sách mượn tối đa không được nhỏ hơn 0'),

            'max_days.required' => __('Thời hạn mượn (ngày) không được để trống'),
            'max_days.integer' => __('Thời hạn mượn phải là số nguyên'),
            'max_days.min' => __('Thời hạn mượn không được nhỏ hơn 0'),

            'max_renewals.required' => __('Số lần gia hạn tối đa không được để trống'),
            'max_renewals.integer' => __('Số lần gia hạn tối đa phải là số nguyên'),
            'max_renewals.min' => __('Số lần gia hạn tối đa không được nhỏ hơn 0'),

            'overdue_fine_per_day.required' => __('Phạt mỗi ngày trễ hạn không được để trống'),
            'overdue_fine_per_day.decimal' => __('Phạt mỗi ngày trễ hạn phải là số, tối đa 2 chữ số thập phân'),
            'overdue_fine_per_day.min' => __('Phạt mỗi ngày trễ hạn không được âm'),

            'allow_home.boolean' => __('Trường “mượn về nhà” phải đúng/sai'),

            'allow_onsite.boolean' => __('Trường “đọc/mượn tại chỗ” phải đúng/sai'),

            'params.array' => __('Tham số mở rộng phải là mảng'),
            'params.max_textbooks.integer' => __('params.max_textbooks phải là số nguyên'),
            'params.max_textbooks.min' => __('params.max_textbooks không được âm'),
            'params.max_reference.integer' => __('params.max_reference phải là số nguyên'),
            'params.max_reference.min' => __('params.max_reference không được âm'),
            'params.damage_fine_percent.numeric' => __('Phạt hư hỏng phải là số (hệ số 0–1, ví dụ 0,1 = 10% giá bìa).'),
            'params.damage_fine_percent.min' => __('Phạt hư hỏng không được âm.'),
            'params.damage_fine_percent.max' => __('Phạt hư hỏng tối đa 100% giá bìa (hệ số 1).'),
        ];
    }
}
