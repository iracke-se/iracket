<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // Logged-in users: DB is the only source of truth
        if (Auth::check()) {
            return Auth::user()->locale ?? $this->detectLocaleFromIp($request);
        }

        // Guests: session, then IP fallback
        if (session()->has('locale')) {
            return session('locale');
        }

        return $this->detectLocaleFromIp($request);
    }

    private function detectLocaleFromIp(Request $request): string
    {
        $ip = $request->ip();

        // Local / private IPs — default to Swedish (dev / local environment)
        if ($this->isPrivateIp($ip)) {
            return 'sv';
        }

        $country = Cache::get("ip_country_{$ip}");

        if ($country === null) {
            try {
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'countryCode',
                ]);
                $code = $response->json('countryCode');
                if ($response->successful() && preg_match('/^[A-Z]{2}$/', (string) $code)) {
                    $country = $code;
                    Cache::put("ip_country_{$ip}", $country, now()->addDay());
                }
            } catch (\Throwable) {
                // don't cache failures, retry next request
            }
        }

        return $country === 'SE' ? 'sv' : 'en';
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
