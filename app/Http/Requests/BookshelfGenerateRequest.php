<?php

namespace App\Http\Requests;

class BookshelfGenerateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'reset' => ['sometimes', 'boolean'],
            'max_rows' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'max_columns' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
