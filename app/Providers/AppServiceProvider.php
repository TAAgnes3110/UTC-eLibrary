<?php

namespace App\Providers;

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
        Vite::prefetch(concurrency: 3);
        $this->bootMicrosoftAzureSocialite();
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
