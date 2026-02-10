<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Backend\EmailOTPController;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequests\RegisterRequest;
use App\Http\Requests\OtpRequests\VerifyOTPRequest;
use App\Models\User;
use App\Services\OtpService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        if ($existingUser = User::duplicate($data)->first()) {
            $errorField = 'email';
            $errorMessage = __('Thông tin đã được sử dụng.');
            if ($existingUser->email === $data['email']) {
                $errorField = 'email';
                $errorMessage = __('Email đã tồn tại trong hệ thống.');
            } elseif ($existingUser->code === $data['code']) {
                $errorField = 'code';
                $errorMessage = __('Mã số (MSV/CCCD) đã tồn tại trong hệ thống.');
            } elseif (!empty($data['phone']) && $existingUser->phone === $data['phone']) {
                $errorField = 'phone';
                $errorMessage = __('Số điện thoại đã tồn tại trong hệ thống.');
            }
            throw ValidationException::withMessages([
                $errorField => $errorMessage,
            ]);
        }
        try {
            $otpService = app(OtpService::class);
            $result = $otpService->sendOtp($data['email'], $data['name']);
            if (!$result['status']) {
                return back()->withErrors(['email' => $result['message']]);
            }
            Cache::put('register_' . $data['email'], $data, now()->addMinutes(15));
            return redirect()->route('verify-otp', ['email' => $data['email']])->with([
                'status' => 'Mã xác thực đã được gửi đến email. Vui lòng kiểm tra để hoàn tất đăng ký.',
            ]);
        } catch (Exception $e) {
            Cache::forget('register_' . $data['email']);
            return back()->withErrors(['email' => 'Lỗi hệ thống: ' . $e->getMessage()]);
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
        $otpService = app(OtpService::class);
        $otpCheck = $otpService->verifyOtp($request->email, $request->otp);
        if (!$otpCheck['status']) {
            throw ValidationException::withMessages([
                'otp' => $otpCheck['message'],
            ]);
        }
        $pendingUser = Cache::get('register_' . $request->email);
        if (!$pendingUser) {
            throw ValidationException::withMessages([
                'otp' => __('Phiên đăng ký đã hết hạn hoặc không tồn tại.'),
            ]);
        }
        if ($existingUser = User::duplicate($pendingUser)->first()) {
            Cache::forget('register_' . $request->email);
            throw ValidationException::withMessages([
                'email' => __('Tài khoản với thông tin này đã được đăng ký thành công trước đó.'),
            ]);
        }
        DB::beginTransaction();
        try {
            $user = User::create($pendingUser);
            $user->email_verified_at = now();
            $user->save();

            $user->libraryCard()->create([
                'card_number' => $user->code,
                'status' => 'active',
                'is_active' => true,
                'issue_date' => now(),
            ]);
            DB::commit();
            Cache::forget('register_' . $request->email);
            return redirect()->route('login')->with('status', 'Đăng ký tài khoản thành công! Vui lòng đăng nhập.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['otp' => 'Lỗi tạo tài khoản: ' . $e->getMessage()]);
        }
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        if (!Cache::has('register_' . $email)) {
            return redirect()->route('register')->withErrors(['email' => 'Phiên đăng ký đã hết hạn. Vui lòng đăng ký lại.']);
        }

        try {
            $pendingUser = Cache::get('register_' . $email);
            $otpService = app(OtpService::class);
            $result = $otpService->sendOtp($email, $pendingUser['name'] ?? 'Người dùng');
            if (!$result['status']) {
                return back()->withErrors(['otp' => $result['message']]);
            }
            return back()->with([
                'email' => $email,
                'status' => 'Đã gửi lại mã OTP. Vui lòng kiểm tra email.',
            ]);
        } catch (Exception $e) {
            return back()->withErrors(['otp' => $e->getMessage()]);
        }
    }
}
