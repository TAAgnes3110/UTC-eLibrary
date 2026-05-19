<?php

namespace App\Providers;

use App\Enums\RoleType;
use App\Support\Database\MigrationBlueprintMacros;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\SocialiteManager;
use SocialiteProviders\Azure\Provider;

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
        MigrationBlueprintMacros::register();

        if (str_contains(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        // Production/staging: không đọc public/hot — tránh nhầm upload hot file → HTML trỏ [::1]:5173 + CORS.
        if (! $this->app->environment('local')) {
            Vite::useHotFile(storage_path('framework/vite-remote-no-hmr'));
        }

        Inertia::share('notifications', fn () => [
            'poll_interval_ms' => (int) config('notifications.ui_poll_interval_ms', 30_000),
        ]);

        // @intelephense-ignore-next-line
        Inertia::share('auth', function () {
            $user = request()->user();
            if (! $user) {
                return ['user' => null, 'is_staff' => false];
            }
            $staffRoles = RoleType::staffRoles();
            $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : ($user->user_type ?? null);
            $isStaff = $roleValue && in_array($roleValue, $staffRoles, true);
            $avatar = $user->avatar ?? '';
            if ($avatar && ! str_starts_with($avatar, 'http')) {
                /** @var FilesystemAdapter $mediaStorage */
                $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
                $avatar = $mediaStorage->url($avatar);
            }
            if (empty($avatar)) {
                $avatar = null;
            }

            return [
                'user' => [
                    'id' => $user->id,
                    'code' => $user->code,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $avatar,
                    'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                    'gender' => $user->gender,
                    'address' => $user->address,
                    'roles' => $user->getRoleNames()->values()->all(),
                    'is_active' => (bool) $user->is_active,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'user_type' => $user->user_type instanceof RoleType ? $user->user_type->value : $user->user_type,
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
                ? 'api:user:'.$request->user()->id
                : 'api:ip:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('refresh', function (Request $request) {
            $key = $request->user()?->id
                ? 'refresh:user:'.$request->user()->id
                : 'refresh:ip:'.$request->ip();

            return Limit::perMinute(30)->by($key);
        });

        RateLimiter::for('session_token', function (Request $request) {
            $key = $request->user()?->id
                ? 'session-token:user:'.$request->user()->id
                : 'session-token:ip:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });
    }

    protected function bootMicrosoftAzureSocialite()
    {
        /** @var SocialiteManager $socialite */
        $socialite = $this->app->make(SocialiteFactory::class);

        $socialite->extend('microsoft-azure', function ($app) use ($socialite) {
            $config = config('services.azure');

            return $socialite->buildProvider(Provider::class, $config);
        });
    }
}
