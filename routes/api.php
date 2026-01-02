<?php

use App\Http\Controllers\Api\MobileAppController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::middleware('mobile_app')->prefix('mobile')->group(function () {
    Route::post('/fcm-token', [MobileAppController::class, 'storeFcmToken']);
    Route::delete('/fcm-token', [MobileAppController::class, 'removeFcmToken']);
});

// Heartbeat endpoint for admin monitoring
Route::middleware(['auth:sanctum', 'role:Admin'])->get('/heartbeat', function () {
    $timeout = config('heartbeat.timeout', 5);

    $checkHeartbeat = function (string $cacheKey, bool $enabled, string $name) use ($timeout) {
        if (!$enabled) {
            return ['status' => 'disabled', 'name' => $name];
        }

        $heartbeat = Cache::get(config('heartbeat.cache_prefix') . $cacheKey);

        if (!$heartbeat) {
            return ['status' => 'unknown', 'name' => $name, 'last_beat' => null];
        }

        $lastBeat = now()->createFromTimestamp($heartbeat['unix_timestamp']);
        $minutesAgo = now()->diffInMinutes($lastBeat);

        return [
            'status' => $minutesAgo > $timeout ? 'unhealthy' : 'healthy',
            'name' => $name,
            'last_beat' => $heartbeat['timestamp'],
            'minutes_ago' => $minutesAgo,
        ];
    };

    $scheduler = $checkHeartbeat(
        config('heartbeat.scheduler.cache_key'),
        config('heartbeat.scheduler.enabled'),
        'scheduler'
    );

    $queue = $checkHeartbeat(
        config('heartbeat.queue.cache_key'),
        config('heartbeat.queue.enabled'),
        'queue'
    );

    $overallHealthy = ($scheduler['status'] === 'healthy' || $scheduler['status'] === 'disabled')
        && ($queue['status'] === 'healthy' || $queue['status'] === 'disabled' || $queue['status'] === 'unknown');

    return response()->json([
        'status' => $overallHealthy ? 'healthy' : 'unhealthy',
        'timestamp' => now()->toIso8601String(),
        'components' => [
            'scheduler' => $scheduler,
            'queue' => $queue,
        ],
    ], $overallHealthy ? 200 : 503);
});
