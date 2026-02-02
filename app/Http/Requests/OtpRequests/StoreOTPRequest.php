<?php

namespace App\Http\Requests\OtpRequests;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreOTPRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }
}
