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

            return redirect()->route('verify-otp', ['email' => $data['email']])->with([
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
        $email = $request->email ?? session('email');
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
            $userData = $pendingUser;
            // Store extra fields in params since columns were removed
            $userData['params'] = [
                'organization' => $pendingUser['organization'] ?? null,
                'province' => $pendingUser['province'] ?? null,
            ];

            $user = User::create($userData);
            $user->email_verified_at = now();
            $user->save();

            Cache::forget('register_' . $request->email);

            return redirect()->route('login')->with('status', 'Đăng ký tài khoản thành công! Vui lòng đăng nhập.');
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
        $cooldownKey = 'otp_cooldown_' . $email;
        if (Cache::has($cooldownKey)) {
            $seconds = Cache::get($cooldownKey) - time();
            throw \Illuminate\Validation\ValidationException::withMessages([
                'otp' => "Vui lòng đợi {$seconds} giây nữa để gửi lại mã.",
            ]);
        }
        app(EmailOTPController::class)->sendOtp($email, $pendingUser['name']);
        Cache::put($cooldownKey, time() + 60, 60);

        return back()->with([
            'email' => $email,
            'status' => 'Đã gửi lại mã OTP. Vui lòng kiểm tra email.',
        ]);
    }
}
