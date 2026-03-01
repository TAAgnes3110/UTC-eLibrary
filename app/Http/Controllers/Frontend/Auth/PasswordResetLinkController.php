<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Api\EmailOTPController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Email không tồn tại trong hệ thống.',
            ]);
        }
        $otpService = app(OtpService::class);
        $result = $otpService->sendOtp($user->email, $user->name);

        if (!$result['status']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => $result['message'],
            ]);
        }

        return redirect()->route('password.reset', ['email' => $user->email])->with([
            'status' => 'Đã gửi mã OTP đến email của bạn. Vui lòng kiểm tra.',
        ]);
    }
}
