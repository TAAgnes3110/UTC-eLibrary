<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Mail\SendOTP;
use Illuminate\Support\Facades\Mail;
use Exception;

class OtpService
{
  /**
   * Gửi mã OTP đến email (có giới hạn thời gian gửi lại 90s)
   *
   * @param string $email
   * @param string $name
   * @return array ['status' => bool, 'message' => string, 'otp' => string|null]
   * @throws Exception
   */
  public function sendOtp(string $email, string $name = 'Người dùng'): array
  {
    $lastOtp = EmailOtp::where('email', $email)->first();
    if ($lastOtp && $lastOtp->updated_at->addSeconds(90)->isFuture()) {
      $secondsLeft = $lastOtp->updated_at->addSeconds(90)->diffInSeconds(now());
      return [
        'status' => false,
        'message' => "Vui lòng đợi {$secondsLeft} giây trước khi yêu cầu mã mới.",
        'seconds_left' => $secondsLeft
      ];
    }

    $otp = (string) rand(100000, 999999);
    $expired_at = now()->addMinutes(10);

    EmailOtp::updateOrCreate(
      ['email' => $email],
      [
        'otp' => $otp,
        'expired_at' => $expired_at,
        'updated_at' => now(),
      ]
    );
    try {
      Mail::to($email)->send(new SendOTP([
        'otp' => $otp,
        'name' => $name
      ]));

      return [
        'status' => true,
        'message' => __('Mã xác thực đã được gửi đến email. Vui lòng kiểm tra để hoàn tất đăng ký.'),
        'otp' => $otp
      ];
    } catch (Exception $e) {
      return [
        'status' => false,
        'message' => 'Lỗi gửi email: ' . $e->getMessage()
      ];
    }
  }

  /**
   * Kiểm tra mã OTP
   *
   * @param string $email
   * @param string $otp
   * @return array ['status' => bool, 'message' => string]
   */
  public function verifyOtp(string $email, string $otp): array
  {
    $otpRecord = EmailOtp::where('email', $email)->first();

    if (!$otpRecord) {
      return ['status' => false, 'message' => __('Yêu cầu OTP không tồn tại.')];
    }

    if ($otpRecord->otp !== $otp) {
      return ['status' => false, 'message' => __('Mã OTP không chính xác.')];
    }

    if ($otpRecord->expired_at && $otpRecord->expired_at->isPast()) {
      $otpRecord->delete();
      return ['status' => false, 'message' => __('Mã OTP đã hết hạn.')];
    }
    $otpRecord->delete();

    return ['status' => true, 'message' => __('Xác thực thành công.')];
  }
}
