<?php

namespace App\Http\Requests;

class AuthorRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('author');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'params' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên tác giả không được để trống',
        ];
    }
}
