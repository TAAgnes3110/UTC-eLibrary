<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên tác giả là bắt buộc.'
        ];
    }
}
