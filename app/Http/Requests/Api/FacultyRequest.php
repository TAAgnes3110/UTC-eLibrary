<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class FacultyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
