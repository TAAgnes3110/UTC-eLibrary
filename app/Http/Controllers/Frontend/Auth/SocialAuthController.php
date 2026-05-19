<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToMicrosoft(): RedirectResponse
    {
        return Socialite::driver('microsoft-azure')->redirect();
    }

    public function handleMicrosoftCallback(): RedirectResponse
    {
        try {
            $microsoftUser = Socialite::driver('microsoft-azure')->user();
        } catch (Exception $e) {
            return redirect()
                ->route('login')
                ->with('status', 'Lỗi kết nối tài khoản Microsoft: '.$e->getMessage());
        }

        $email = trim((string) $microsoftUser->getEmail());
        if ($email === '') {
            return redirect()
                ->route('login')
                ->with('status', 'Không lấy được email từ tài khoản Microsoft. Vui lòng cấp quyền email hoặc dùng đăng nhập email/mật khẩu.');
        }

        $name = (string) ($microsoftUser->getName() ?: Str::before($email, '@'));
        $msId = (string) $microsoftUser->getId();
        $userType = $this->resolveUserType($email);
        $code = $this->resolveUserCode($email, $msId);
        $cohort = $userType === RoleType::STUDENT
            ? $this->extractCohortFromStudentCode($code)
            : null;

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'code' => $code,
                'password' => Hash::make($msId),
                'email_verified_at' => now(),
                'user_type' => $userType,
                'avatar' => $microsoftUser->getAvatar(),
                'cohort' => $cohort,
            ]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        $roleValue = $user->user_type instanceof RoleType
            ? $user->user_type->value
            : (string) ($user->user_type ?? '');
        $isStaff = $roleValue !== '' && in_array($roleValue, RoleType::staffRoles(), true);

        return redirect()->intended(route($isStaff ? 'admin.dashboard' : 'reader.home'));
    }

    /**
     * Mặc định bạn đọc (MEMBER). Chỉ gán STUDENT khi email thuộc miền sinh viên UTC.
     */
    private function resolveUserType(string $email): RoleType
    {
        $domain = strtolower(Str::after($email, '@'));

        if ($domain === 'st.utc.edu.vn') {
            return RoleType::STUDENT;
        }

        return RoleType::MEMBER;
    }

    private function resolveUserCode(string $email, string $msId): string
    {
        $fromEmail = $this->extractCodeFromEmail($email);
        if ($fromEmail !== null && strlen($fromEmail) >= 9 && strlen($fromEmail) <= 12) {
            return $this->ensureUniqueCode($fromEmail);
        }

        $digits = preg_replace('/\D/', '', $msId) ?? '';
        if (strlen($digits) >= 9) {
            return $this->ensureUniqueCode(substr($digits, 0, 12));
        }

        return $this->ensureUniqueCode(str_pad((string) abs(crc32($msId)), 9, '0', STR_PAD_LEFT));
    }

    private function ensureUniqueCode(string $base): string
    {
        $base = substr($base, 0, 12);
        $code = $base;
        $suffix = 0;

        while (User::query()->where('code', $code)->exists()) {
            $suffix++;
            $code = substr($base, 0, max(9, 12 - strlen((string) $suffix))).$suffix;
        }

        return $code;
    }

    private function extractCodeFromEmail(string $email): ?string
    {
        $username = explode('@', $email)[0] ?? '';
        if (preg_match('/^[a-zA-Z]*(\d+)$/', $username, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Lấy khóa học từ mã sinh viên UTC: 3 chữ số đầu là "223", 2 chữ số tiếp theo là khóa.
     */
    private function extractCohortFromStudentCode(?string $code): ?string
    {
        if ($code === null || strlen($code) < 5) {
            return null;
        }
        if (str_starts_with($code, '223')) {
            return 'K'.substr($code, 3, 2);
        }

        return null;
    }
}
