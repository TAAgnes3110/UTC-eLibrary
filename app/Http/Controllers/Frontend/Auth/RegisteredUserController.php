<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequests\RegisterRequest;
use App\Http\Requests\OtpRequests\VerifyOTPRequest;
use App\Models\User;
use App\Http\Controllers\Backend\EmailOTPController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            Cache::put('register_' . $data['email'], $data, 600);
            app(EmailOTPController::class)->sendOtp($data['email'], $data['name']);

            return redirect()->route('verify-otp')->with([
                'email' => $data['email'],
                'status' => 'Vui lòng kiểm tra email để lấy mã OTP xác nhận tài khoản.',
            ]);
        } catch (\Exception $e) {
            Cache::forget('register_' . $data['email']);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Lỗi gửi OTP: ' . $e->getMessage(),
            ]);
        }
    }

    public function verifyOtpPage(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('email') ?? $request->query('email');
        if (!$email) {
            return redirect()->route('register');
        }
        return Inertia::render('Auth/VerifyOtp', [
            'email' => $email,
            'status' => session('status'),
        ]);
    }

    public function verifyOtp(VerifyOTPRequest $request): RedirectResponse
    {
        $otpCheck = app(EmailOTPController::class)->checkOTP($request->email, $request->otp);

        if (!$otpCheck['status']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'otp' => $otpCheck['message'],
            ]);
        }

        $pendingUser = Cache::get('register_' . $request->email);

        if ($pendingUser) {
            $user = User::create($pendingUser);
            $user->email_verified_at = now();
            $user->save();

            Cache::forget('register_' . $request->email);

            Auth::guard('web')->login($user);

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'otp' => 'Phiên đăng ký đã hết hạn. Vui lòng đăng ký lại.',
        ]);
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;
        $pendingUser = Cache::get('register_' . $email);

        if (!$pendingUser) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Phiên đăng ký đã hết hạn. Vui lòng đăng ký lại.',
            ]);
        }

        app(EmailOTPController::class)->sendOtp($email, $pendingUser['name']);

        return back()->with('status', 'Đã gửi lại mã OTP. Vui lòng kiểm tra email.');
    }
}
