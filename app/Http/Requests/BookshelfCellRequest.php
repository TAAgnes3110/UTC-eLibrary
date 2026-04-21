<?php

namespace App\Http\Requests;

class BookshelfCellRequest extends BaseRequest
{
    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'warehouse_id' => [
                $isCreate ? 'required' : 'sometimes',
                'integer',
                'exists:warehouses,id',
            ],
            'label' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:120'],
            'classification_id' => [$isCreate ? 'required' : 'sometimes', 'integer', 'exists:classifications,id'],
            'classification_detail_id' => [$isCreate ? 'required' : 'sometimes', 'integer', 'exists:classification_details,id'],
            'is_active' => ['sometimes', 'boolean'],
            'params' => ['sometimes', 'nullable', 'array'],
            'row_index' => [$isCreate ? 'required' : 'sometimes', 'integer', 'min:1'],
            'column_index' => [$isCreate ? 'required' : 'sometimes', 'integer', 'min:1'],
        ];
    }
}
