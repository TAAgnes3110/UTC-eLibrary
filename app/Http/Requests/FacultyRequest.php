<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacultyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            "name" => 'required|string|max:255',
        ];
    }
}
