<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use SocialiteProviders\Apple\Provider as AppleProvider;

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
        // Register Apple Socialite provider
        $socialite = $this->app->make(SocialiteFactory::class);

        $socialite->extend('apple', function ($app) use ($socialite) {
            $config = $app['config']['services.apple'];
            return $socialite->buildProvider(AppleProvider::class, $config);
        });
    }
}
