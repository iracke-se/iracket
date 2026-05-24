<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Developer Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, only the public landing page ("/") remains reachable
    | for ordinary visitors. Every other route (login, registration, app,
    | admin, etc.) is replaced with a maintenance page.
    |
    | Requests originating from an IP in `allowed_ips` bypass the gate
    | entirely and see the site as normal — intended for developers /
    | operators continuing to work while users are locked out.
    |
    | This is independent of Laravel's built-in `php artisan down` flag.
    |
    */

    'enabled' => filter_var(env('DEV_MAINTENANCE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DEV_MAINTENANCE_ALLOWED_IPS', ''))
    ))),

];
