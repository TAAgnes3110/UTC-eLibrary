<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{

  public function create(): Response
  {
    return Inertia::render('Auth/Login', [
      'canResetPassword' => Route::has('password.request'),
      'status' => session('status'),
    ]);
  }

  public function store(LoginRequest $request): RedirectResponse
  {
    $loginField = $request->input('login');
    $password = $request->input('password');
    $remember = $request->boolean('remember');

    $user = \App\Models\User::query()
      ->where('email', $loginField)
      ->orWhere('code', $loginField)
      ->orWhere('phone', $loginField)
      ->orWhere('card_number', $loginField)
      ->first();

    if (!$user) {
      throw \Illuminate\Validation\ValidationException::withMessages([
        'login' => __('Tài khoản không tồn tại trong hệ thống'),
      ]);
    }

    if (!Auth::guard('web')->attempt(['email' => $user->email, 'password' => $password], $remember)) {
      throw \Illuminate\Validation\ValidationException::withMessages([
        'login' => __('Tài khoản hoặc mật khẩu không chính xác'),
      ]);
    }

    $request->session()->regenerate();

    return redirect()->intended(route('dashboard'));
  }

  public function destroy(Request $request): RedirectResponse
  {
    Auth::guard('web')->logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return redirect('/');
  }
}
