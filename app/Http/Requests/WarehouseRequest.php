<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class WarehouseRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('warehouse');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        return [
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')->ignore($id),
            ],
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:warehouses,id'],
            'is_active' => ['sometimes', 'boolean'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Mã kho không được để trống',
            'code.string' => 'Mã kho phải là chuỗi',
            'code.max' => 'Mã kho không được vượt quá 255 ký tự',
            'code.unique' => 'Mã kho đã tồn tại trong hệ thống',
            'name.required' => 'Tên kho không được để trống',
            'name.string' => 'Tên kho phải là chuỗi',
            'name.max' => 'Tên kho không được vượt quá 255 ký tự',
        ];
    }
}
