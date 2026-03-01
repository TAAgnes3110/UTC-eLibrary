<?php

namespace App\Http\Requests;

class DepartmentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
        ];
    }
}
