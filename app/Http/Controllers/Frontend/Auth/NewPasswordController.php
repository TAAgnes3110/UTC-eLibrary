<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Trang đặt lại mật khẩu (GET). Submit: POST /api/v1/auth/reset-password qua axios.
 */
class NewPasswordController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('email') ?? $request->query('email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ResetPassword', [
            'email' => $email,
            'status' => session('status'),
        ]);
    }
}
