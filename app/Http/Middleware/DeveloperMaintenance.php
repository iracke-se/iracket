<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeveloperMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('dev_maintenance.enabled')) {
            return $next($request);
        }

        if ($this->isAllowedIp($request)) {
            return $next($request);
        }

        if ($this->isAllowedPath($request)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503)
            ->header('Retry-After', '3600');
    }

    private function isAllowedIp(Request $request): bool
    {
        $allowed = (array) config('dev_maintenance.allowed_ips', []);

        return in_array($this->clientIp($request), $allowed, true);
    }

    private function clientIp(Request $request): string
    {
        $cf = $request->header('CF-Connecting-IP');

        if (is_string($cf) && filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }

        return (string) $request->ip();
    }

    private function isAllowedPath(Request $request): bool
    {
        return $request->is('/')
            || $request->is('locale/*')
            || $request->is('livewire/*')
            || $request->is('build/*')
            || $request->is('up');
    }
}
