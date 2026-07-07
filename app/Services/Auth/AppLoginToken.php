<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Short-lived, single-use tokens that hand a server-verified identity from the
 * OAuth secure-browser flow back into the Flutter WebView's own session.
 *
 * The OAuth callback runs in the system browser (ASWebAuthenticationSession),
 * whose cookies are NOT shared with the WebView, so its session cannot log the
 * WebView in directly. Instead the callback issues one of these tokens and
 * redirects to iracket://auth-callback?token=…; the app then loads
 * /auth/app-login?token=… inside the WebView, which consumes the token and
 * establishes the session there.
 */
class AppLoginToken
{
    private const PREFIX = 'app_login:';
    private const TTL_MINUTES = 5;

    /**
     * Issue a token for the given user and post-login destination path.
     */
    public static function issue(int $userId, string $next): string
    {
        $token = Str::random(64);

        Cache::put(self::PREFIX.$token, [
            'user_id' => $userId,
            'next' => $next,
        ], now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /**
     * Consume a token, returning its payload once and only once. Returns null
     * if the token is missing, already used, or expired.
     *
     * @return array{user_id:int, next:string}|null
     */
    public static function consume(string $token): ?array
    {
        $key = self::PREFIX.$token;
        $data = Cache::get($key);

        if ($data === null) {
            return null;
        }

        // Single use: invalidate immediately so a leaked token cannot be replayed.
        Cache::forget($key);

        return $data;
    }
}
