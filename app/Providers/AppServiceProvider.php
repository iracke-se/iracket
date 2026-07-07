<?php

namespace App\Providers;

use Illuminate\Http\Request;
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

        // Detect requests coming from the iRacket Flutter WebView. The app sends
        // `?source=app` on the initial load and an "iRacketApp" user-agent marker
        // on every request. Used to hide website-only UI (e.g. the "Back to
        // Website" link, which points to the home page the app never shows) and
        // to redirect app traffic off the public landing page.
        Request::macro('isWebviewApp', function (): bool {
            /** @var \Illuminate\Http\Request $this */
            if ($this->query('source') === 'app') {
                return true;
            }

            if ($this->header('X-App-Source') === 'flutter') {
                return true;
            }

            $userAgent = (string) $this->userAgent();

            foreach (['iRacketApp', 'Flutter', '; wv)', 'WebView'] as $needle) {
                if (stripos($userAgent, $needle) !== false) {
                    return true;
                }
            }

            return false;
        });
    }
}
