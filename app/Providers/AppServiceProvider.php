<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use SocialiteProviders\Apple\Provider as AppleProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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

        // Register Brevo (Sendinblue) API mail transport.
        // The named "brevo" mailer in config/mail.php resolves to this creator.
        Mail::extend('brevo', fn () => (new BrevoTransportFactory)->create(
            new Dsn('brevo+api', 'default', config('services.brevo.key'))
        ));
    }
}
