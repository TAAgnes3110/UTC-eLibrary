<?php

namespace App\Http\Requests\OtpRequests;

use App\Http\Requests\BaseRequest;

class SendOTPRequest extends BaseRequest
{
  public function rules(): array
  {
    return [
      'email' => 'required|email|max:255',
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'Email không được để trống',
      'email.email' => 'Email không hợp lệ',
    ];
  }
}
