<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDbSyncSecret
{
    /**
     * Guard the db-sync API: the request must carry the shared secret in the
     * X-Db-Sync-Secret header and it must match this instance's configured
     * secret. If no secret is configured, the endpoint is fully disabled.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('datasync.secret');
        $provided = (string) $request->header('X-Db-Sync-Secret', '');

        if ($configured === '' || ! hash_equals($configured, $provided)) {
            abort(403, 'Invalid or missing sync secret.');
        }

        return $next($request);
    }
}
