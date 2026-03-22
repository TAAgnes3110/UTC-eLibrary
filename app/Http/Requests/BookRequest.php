<?php

namespace App\Http\Requests;

use App\Enums\AccessMode;
use App\Enums\ResourceKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        return [
            'title' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'warehouse_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'exists:warehouses,id',
            ],
            'quantity' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'min:0',
            ],
            'resource_kind' => ['sometimes', 'nullable', Rule::enum(ResourceKind::class)],
            'access_mode' => ['sometimes', 'nullable', Rule::enum(AccessMode::class)],
            'thesis_metadata' => ['sometimes', 'nullable', 'array'],
            'thesis_metadata.work_type' => ['sometimes', 'nullable', 'string', 'max:40'],
            'thesis_metadata.degree_program' => ['sometimes', 'nullable', 'string', 'max:150'],
            'thesis_metadata.supervisor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'thesis_metadata.supervisor_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'thesis_metadata.defense_year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:2100'],
            'thesis_metadata.keywords' => ['sometimes', 'nullable', 'string'],
            'thesis_metadata.abstract_text' => ['sometimes', 'nullable', 'string'],
            'thesis_metadata.params' => ['sometimes', 'nullable', 'array'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'book_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'published_year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:2100'],
            'price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'classification_id' => ['sometimes', 'nullable', 'integer', 'exists:classifications,id'],
            'classification_detail_id' => ['sometimes', 'nullable', 'integer', 'exists:classification_details,id'],
        ];
    }

    public function messages(): array {
        return [
            'title.required' => __('Tên sách không được để trống'),
            'title.string' => __('Tên sách phải là một chuỗi'),
            'title.max' => __('Tên sách không được vượt quá 255 ký tự'),
            'warehouse_id.required' => __('Kho sách không được để trống'),
            'warehouse_id.exists' => __('Kho sách không tồn tại'),
            'quantity.required' => __('Số lượng không được để trống'),
            'quantity.integer' => __('Số lượng phải là một số nguyên'),
            'quantity.min' => __('Số lượng không được nhỏ hơn 0'),
            'classification_id.exists' => __('Phân loại sách không tồn tại'),
            'classification_detail_id.exists' => __('Phân loại chi tiết không tồn tại'),
            'published_year.min' => __('Năm xuất bản không hợp lệ'),
            'published_year.max' => __('Năm xuất bản không hợp lệ'),
            'price.min' => __('Giá không được âm'),
        ];
    }
}
