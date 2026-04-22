<?php

namespace App\Http\Requests;

class StorageSlotRequest extends BaseRequest
{
    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'classification_detail_id' => [$isCreate ? 'required' : 'sometimes', 'integer', 'exists:classification_details,id'],
            'slot_code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'slot_name' => ['sometimes', 'nullable', 'string', 'max:180'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'current_quantity' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
