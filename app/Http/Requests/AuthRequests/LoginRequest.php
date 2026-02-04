<?php

namespace App\Http\Requests\AuthRequests;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'login' => 'required',
            'password' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Login is required.',
            'password.required' => 'Password is required.',
        ];
    }
}
