<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StorageCabinetRequest extends BaseRequest
{
    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');
        $cabinet = $this->route('storageCabinet');
        $id = is_object($cabinet) ? $cabinet->id : $cabinet;

        return [
            'warehouse_id' => [$isCreate ? 'required' : 'sometimes', 'integer', 'exists:warehouses,id'],
            'classification_id' => [$isCreate ? 'required' : 'sometimes', 'integer', 'exists:classifications,id'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:60',
                Rule::unique('storage_cabinets', 'code')->ignore($id),
            ],
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:160'],
            'is_active' => ['sometimes', 'boolean'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
