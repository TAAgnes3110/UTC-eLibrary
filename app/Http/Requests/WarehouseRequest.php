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
                $isUpdate ? 'sometimes' : 'nullable',
                'nullable',
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
            'shelf_count' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.nullable' => 'Mã kho không hợp lệ',
            'code.string' => 'Mã kho phải là chuỗi',
            'code.max' => 'Mã kho không được vượt quá 255 ký tự',
            'code.unique' => 'Mã kho đã tồn tại trong hệ thống',
            'name.required' => 'Tên kho không được để trống',
            'name.string' => 'Tên kho phải là chuỗi',
            'name.max' => 'Tên kho không được vượt quá 255 ký tự',
            'parent_id.exists' => 'Kho cha không tồn tại trong hệ thống',
            'parent_id.integer' => 'Kho cha không hợp lệ',
            'is_active.boolean' => 'Trạng thái hoạt động không hợp lệ',
            'shelf_count.integer' => 'Số lượng kệ phải là số nguyên',
            'shelf_count.min' => 'Số lượng kệ không được nhỏ hơn 0',
            'shelf_count.max' => 'Số lượng kệ tối đa là 50',
        ];
    }
}
