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
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
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

        $profile = $this->resolveMicrosoftProfile($microsoftUser);
        if ($profile['email'] === '') {
            return redirect()
                ->route('login')
                ->with('status', 'Không lấy được email từ tài khoản Microsoft. Vui lòng cấp quyền email hoặc dùng đăng nhập email/mật khẩu.');
        }

        $msId = (string) $microsoftUser->getId();
        $code = $this->resolveUserCode($profile['email'], $msId);

        $user = User::query()->where('email', $profile['email'])->first();
        if (! $user) {
            $user = User::query()->create([
                'name' => $profile['name'],
                'email' => $profile['email'],
                'code' => $code,
                'password' => Hash::make($msId),
                'email_verified_at' => now(),
                'user_type' => RoleType::MEMBER,
                'avatar' => $profile['avatar'],
            ]);
        } else {
            $updates = array_filter([
                'name' => $profile['name'] !== '' ? $profile['name'] : null,
                'avatar' => $profile['avatar'],
            ], fn ($value) => $value !== null && $value !== '');
            if ($updates !== []) {
                $user->update($updates);
            }
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
     * Lấy email, tên hiển thị từ Microsoft Graph (/me).
     * Ưu tiên mail thật; fallback userPrincipalName khi hợp lệ.
     *
     * @return array{email: string, name: string, avatar: ?string}
     */
    private function resolveMicrosoftProfile(SocialiteUserContract $microsoftUser): array
    {
        $raw = $this->microsoftUserRaw($microsoftUser);

        $email = $this->normalizeMicrosoftEmail(
            (string) ($raw['mail'] ?? ''),
            (string) ($microsoftUser->getEmail() ?? ''),
            (string) ($raw['userPrincipalName'] ?? '')
        );

        $name = trim((string) ($raw['displayName'] ?? $microsoftUser->getName() ?? ''));
        if ($name === '' && $email !== '') {
            $name = Str::before($email, '@');
        }

        return [
            'email' => $email,
            'name' => $name,
            'avatar' => $microsoftUser->getAvatar(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function microsoftUserRaw(SocialiteUserContract $microsoftUser): array
    {
        try {
            $raw = $microsoftUser->getRaw();
        } catch (\Throwable) {
            return [];
        }

        return is_array($raw) ? $raw : [];
    }

    private function normalizeMicrosoftEmail(string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim($candidate));
            if ($candidate === '' || str_contains($candidate, '#ext#')) {
                continue;
            }
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return '';
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
}
