<?php

namespace App\Http\Requests;

class AuthorRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'tieu_su' => ['nullable', 'string', 'max:2000'],
            'birth_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Tên tác giả là bắt buộc.'];
    }
}
