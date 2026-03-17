<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ClassificationDetailRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('classification_detail');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        return [
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('classification_details', 'code')->ignore($id),
            ],
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'classification_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:classifications,id',
            ],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:classification_details,id'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Mã phân loại chi tiết không được để trống'),
            'code.unique' => __('Mã phân loại chi tiết đã tồn tại'),
            'name.required' => __('Tên phân loại chi tiết không được để trống'),
            'classification_id.required' => __('Phân loại chính không được để trống'),
            'classification_id.exists' => __('Phân loại chính không tồn tại'),
            'parent_id.exists' => __('Phân loại chi tiết cha không tồn tại'),
        ];
    }
}
