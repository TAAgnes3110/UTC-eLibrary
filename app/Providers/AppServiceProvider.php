<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Publisher;
use App\Observers\TaxonomyCacheObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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

        Vite::prefetch(concurrency: 3);
        $this->bootMicrosoftAzureSocialite();
        $this->bootRateLimiting();
        Category::observe(TaxonomyCacheObserver::class);
        Publisher::observe(TaxonomyCacheObserver::class);
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
    }

    protected function bootMicrosoftAzureSocialite()
    {
        $socialite = $this->app->make(\Laravel\Socialite\Contracts\Factory::class);

        $socialite->extend('microsoft-azure', function ($app) use ($socialite) {
            $config = $app['config']['services.azure'];
            return $socialite->buildProvider(\SocialiteProviders\Azure\Provider::class, $config);
        });
    }
}
