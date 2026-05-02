<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ClassificationRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('classification');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('classifications', 'code')->ignore($id),
            ],
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('classifications', 'name')->ignore($id),
            ],
            'parent_id' => ['prohibited'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => __('Mã phân loại đã tồn tại'),
            'name.required' => __('Tên phân loại không được để trống'),
            'name.unique' => __('Tên phân loại đã có trong hệ thống'),
            'parent_id.prohibited' => __('Không hỗ trợ phân loại lồng nhau; chỉ dùng đầu mục gốc (000 … 900).'),
        ];
    }
}
