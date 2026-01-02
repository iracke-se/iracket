<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeat extends Command
{
    protected $signature = 'heartbeat:scheduler';

    protected $description = 'Update scheduler heartbeat to indicate the scheduler is running';

    public function handle(): int
    {
        if (!config('heartbeat.scheduler.enabled')) {
            return self::SUCCESS;
        }

        $cacheKey = config('heartbeat.cache_prefix') . config('heartbeat.scheduler.cache_key');

        Cache::put($cacheKey, [
            'timestamp' => now()->toIso8601String(),
            'unix_timestamp' => now()->timestamp,
        ], now()->addHours(24));

        $this->info('Scheduler heartbeat updated at ' . now()->toDateTimeString());

        return self::SUCCESS;
    }
}
