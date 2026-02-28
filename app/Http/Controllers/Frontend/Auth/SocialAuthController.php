<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                ->with('status', 'Lỗi kết nối tài khoản Microsoft: ' . $e->getMessage());
        }
        $email = $microsoftUser->getEmail();
        $name = $microsoftUser->getName();
        $msId = $microsoftUser->getId();
        $code = $this->extractCodeFromEmail($email) ?? ('MS' . $msId);
        $userTypeData = $this->determineUserType($email, $microsoftUser);

        $user = User::where('email', $email)->first();
        if (!$user) {
            $userData = array_merge([
                'name' => $name,
                'email' => $email,
                'code' => $code,
                'password' => Hash::make($msId),
                'email_verified_at' => now(),
                'user_type' => RoleType::MEMBER,
                'avatar' => $microsoftUser->getAvatar(),
            ], $userTypeData);
            $user = User::create($userData);
            $user->libraryCard()->create([
                'card_number' => 'TV-MS-' . $msId,
                'status' => 'active',
                'is_active' => true,
                'issue_date' => now(),
            ]);
        }
        Auth::login($user);
        request()->session()->regenerate();
        $staffRoles = RoleType::staffRoles();
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : ($user->user_type ?? null);
        $isStaff = $roleValue && in_array($roleValue, $staffRoles, true);
        return redirect()->intended(route($isStaff ? 'admin.dashboard' : 'library.dashboard'));
    }

    private function extractCodeFromEmail(string $email): ?string
    {
        $username = explode('@', $email)[0];
        if (preg_match('/^[a-zA-Z]*(\d+)$/', $username, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function determineUserType(string $email, $microsoftUser): array
    {
        return [
            'user_type' => RoleType::MEMBER->value,
        ];
    }
}
