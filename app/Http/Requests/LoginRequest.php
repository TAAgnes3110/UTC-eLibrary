<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'login' => ['required'],
            'password' => ['required'],
        ];
    }
}
