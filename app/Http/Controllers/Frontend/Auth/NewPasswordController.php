<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\EmailOTPController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('email') ?? $request->query('email');
        if (!$email) {
            return redirect()->route('password.request');
        }
        return Inertia::render('Auth/ResetPassword', [
            'email' => $email,
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $otpCheck = app(EmailOTPController::class)->checkOTP($request->email, $request->otp);

        if (!$otpCheck['status']) {
            throw ValidationException::withMessages([
                'otp' => $otpCheck['message'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Tài khoản không tồn tại.',
            ]);
        }

        $user->update(['password' => $request->password]);

        return redirect()->route('login')->with('status', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.');
    }
}
