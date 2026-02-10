<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class emailProvider extends ServiceProvider
{
    public function sendOtp(string $email, string $otp): void
    {
        Mail::html(
            "<h3>Mã OTP của bạn là: <b>{$otp}</b></h3><p>OTP có hiệu lực 5 phút</p>",
            fn($message) =>
            $message->to($email)->subject('Xác thực tài khoản')
        );
    }
}
