<?php

namespace App\Http\Requests\OtpRequests;

use App\Http\Requests\BaseRequest;

/**
 * Form request validate gửi OTP (email).
 */
class SendOTPRequest extends BaseRequest
{
  /**
   * Rule validation.
   *
   * @return array<string, mixed>
   */
  public function rules(): array
  {
    return [
      'email' => 'required|email|max:255',
    ];
  }

  /**
   * Thông báo lỗi.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'email.required' => 'Email không được để trống',
      'email.email' => 'Email không hợp lệ',
    ];
  }
}
