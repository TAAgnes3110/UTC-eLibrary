<?php

namespace App\Http\Requests;

use App\Enums\AccessMode;
use App\Enums\ResourceType;
use Illuminate\Contracts\Validation\ValidationRule;
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
        $currentYear = (int) now()->year;

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
            'resource_type' => ['sometimes', 'nullable', Rule::enum(ResourceType::class)],
            'access_mode' => ['sometimes', 'nullable', Rule::enum(AccessMode::class)],
            'thesis_metadata' => ['sometimes', 'nullable', 'array'],
            'thesis_metadata.work_type' => ['sometimes', 'nullable', 'string', 'max:40'],
            'thesis_metadata.degree_program' => ['sometimes', 'nullable', 'string', 'max:150'],
            'thesis_metadata.supervisor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'thesis_metadata.supervisor_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'thesis_metadata.defense_year' => ['sometimes', 'nullable', 'integer', 'min:1900', "max:{$currentYear}"],
            'thesis_metadata.keywords' => ['sometimes', 'nullable', 'string'],
            'thesis_metadata.abstract_text' => ['sometimes', 'nullable', 'string'],
            'thesis_metadata.params' => ['sometimes', 'nullable', 'array'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'book_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sub_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'language' => ['sometimes', 'nullable', 'string', 'max:50'],
            'authors' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'publisher' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'published_year' => ['sometimes', 'nullable', 'integer', 'min:1900', "max:{$currentYear}"],
            'pages' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'book_size' => ['sometimes', 'nullable', 'string', 'max:50'],
            'price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'cabinet' => ['sometimes', 'nullable', 'string', 'max:255'],
            'classification_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:classifications,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('Tên sách không được để trống'),
            'title.string' => __('Tên sách phải là một chuỗi'),
            'title.max' => __('Tên sách không được vượt quá 255 ký tự'),
            'warehouse_id.required' => __('Kho sách không được để trống'),
            'warehouse_id.exists' => __('Kho sách không tồn tại trong hệ thống. Vui lòng chọn từ danh sách.'),
            'quantity.required' => __('Số lượng không được để trống'),
            'quantity.integer' => __('Số lượng phải là một số nguyên'),
            'quantity.min' => __('Số lượng không được nhỏ hơn 0'),
            'classification_id.required' => __('Phân loại sách không được để trống'),
            'classification_id.exists' => __('Phân loại sách không tồn tại trong hệ thống. Vui lòng chọn từ danh sách.'),
            'published_year.min' => __('Năm xuất bản không hợp lệ'),
            'published_year.max' => __('Năm xuất bản không hợp lệ'),
            'price.min' => __('Giá không được âm'),
        ];
    }
}
