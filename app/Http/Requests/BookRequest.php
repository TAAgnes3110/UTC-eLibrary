<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('book');
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
        ];
    }
}
