<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class QueueHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        if (!config('heartbeat.queue.enabled')) {
            return;
        }

        $cacheKey = config('heartbeat.cache_prefix') . config('heartbeat.queue.cache_key');

        Cache::put($cacheKey, [
            'timestamp' => now()->toIso8601String(),
            'unix_timestamp' => now()->timestamp,
            'queue' => $this->queue ?? 'default',
        ], now()->addHours(24));
    }
}
