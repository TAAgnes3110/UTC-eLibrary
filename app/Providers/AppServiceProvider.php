<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Enums\RoleType;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_contains(config('app.url'), 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Inertia::share('auth', function () {
            $user = request()->user();
            if (!$user) {
                return ['user' => null, 'is_staff' => false];
            }
            $staffRoles = RoleType::staffRoles();
            $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : ($user->user_type ?? null);
            $isStaff = $roleValue && in_array($roleValue, $staffRoles, true);
            $avatar = $user->avatar ?? '';
            if ($avatar && !str_starts_with($avatar, 'http')) {
                $avatar = Storage::disk('public')->exists($avatar)
                    ? asset('storage/' . ltrim($avatar, '/'))
                    : null;
            }
            if (empty($avatar)) {
                $avatar = null;
            }
            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $avatar,
                ],
                'is_staff' => $isStaff,
            ];
        });

        Vite::prefetch(concurrency: 3);
        $this->bootMicrosoftAzureSocialite();
        $this->bootRateLimiting();
    }

    /**
     * Configure API rate limiting: 60 requests per minute per user/IP.
     */
    protected function bootRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id
                ? 'api:user:' . $request->user()->id
                : 'api:ip:' . $request->ip();

            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('refresh', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }

    protected function bootMicrosoftAzureSocialite()
    {
        $socialite = $this->app->make(\Laravel\Socialite\Contracts\Factory::class);

        $socialite->extend('microsoft-azure', function ($app) use ($socialite) {
            $config = config('services.azure');
            return $socialite->buildProvider(\SocialiteProviders\Azure\Provider::class, $config);
        });
    }
}
