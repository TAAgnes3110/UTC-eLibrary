<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\EmailOTPController;
use App\Models\User;
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

        app(EmailOTPController::class)->sendOtp($user->email, $user->name);

        return redirect()->route('password.reset')->with([
            'email' => $user->email,
            'status' => 'Đã gửi mã OTP đến email của bạn. Vui lòng kiểm tra.',
        ]);
    }
}
