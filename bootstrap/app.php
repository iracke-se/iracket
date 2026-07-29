<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);

        // The db-sync receiver must write rows byte-for-byte as sent. Laravel's
        // global TrimStrings + ConvertEmptyStringsToNull would otherwise rewrite
        // every "" in the payload to NULL (and trim whitespace) before the
        // receiver sees it — breaking the exact mirror and throwing NOT-NULL
        // violations (e.g. scraped_matches.player2_name = '' → NULL). Skip both
        // for the sync endpoints so the body arrives verbatim.
        $middleware->convertEmptyStringsToNull(except: [
            fn (Request $request) => $request->is('api/db-sync/*'),
        ]);
        $middleware->trimStrings(except: [
            fn (Request $request) => $request->is('api/db-sync/*'),
        ]);

        // Sign in with Apple returns its authorization response as a cross-origin
        // form_post (Apple's servers POST to our callback via the user's browser),
        // so no CSRF token is present. The route must be exempt or every Apple
        // login 419s — on the website and inside the app.
        $middleware->validateCsrfTokens(except: [
            'auth/apple/callback',
        ]);

        // Authenticated users hitting guest-only pages (/login, /register) must
        // land on a page they can actually view. The framework default sends
        // them to route('home') = '/', but '/' bounces WebView-app requests
        // back to /login (see App\Livewire\Public\Home\Index::mount), producing
        // an infinite redirect loop — the app's blank screen on reopen once the
        // remember-me cookie re-authenticates an expired session.
        $middleware->redirectUsersTo('/players');

        $middleware->web(prepend: [
            \App\Http\Middleware\DeveloperMaintenance::class,
        ], append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'mobile_app' => \App\Http\Middleware\MobileAppAuth::class,
            'connected' => \App\Http\Middleware\EnsureAccountConnected::class,
            'db_sync_secret' => \App\Http\Middleware\VerifyDbSyncSecret::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
