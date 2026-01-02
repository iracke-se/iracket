<?php

namespace App\Livewire\Admin\Dashboard;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class HeartbeatStatus extends Component
{
    public function render()
    {
        $timeout = config('heartbeat.timeout', 5);

        $scheduler = $this->checkHeartbeat(
            config('heartbeat.scheduler.cache_key'),
            config('heartbeat.scheduler.enabled'),
            'scheduler'
        );

        $queue = $this->checkHeartbeat(
            config('heartbeat.queue.cache_key'),
            config('heartbeat.queue.enabled'),
            'queue'
        );

        $overallStatus = $this->determineOverallStatus($scheduler, $queue);

        return view('livewire.admin.dashboard.heartbeat-status', [
            'scheduler' => $scheduler,
            'queue' => $queue,
            'overallStatus' => $overallStatus,
        ]);
    }

    protected function checkHeartbeat(string $cacheKey, bool $enabled, string $name): array
    {
        if (!$enabled) {
            return [
                'status' => 'disabled',
                'name' => $name,
                'message' => 'Monitoring disabled',
            ];
        }

        $cacheKey = config('heartbeat.cache_prefix') . $cacheKey;
        $heartbeat = Cache::get($cacheKey);

        if (!$heartbeat) {
            return [
                'status' => 'unknown',
                'name' => $name,
                'message' => 'No heartbeat found yet',
                'last_beat' => null,
            ];
        }

        $lastBeat = now()->createFromTimestamp($heartbeat['unix_timestamp']);
        $minutesAgo = now()->diffInMinutes($lastBeat);
        $timeout = config('heartbeat.timeout', 5);

        if ($minutesAgo > $timeout) {
            return [
                'status' => 'unhealthy',
                'name' => $name,
                'message' => "Heartbeat is stale ({$minutesAgo} min ago)",
                'last_beat' => $heartbeat['timestamp'],
                'minutes_ago' => $minutesAgo,
            ];
        }

        return [
            'status' => 'healthy',
            'name' => $name,
            'message' => 'Running normally',
            'last_beat' => $heartbeat['timestamp'],
            'minutes_ago' => $minutesAgo,
        ];
    }

    protected function determineOverallStatus(array $scheduler, array $queue): string
    {
        $schedulerHealthy = in_array($scheduler['status'], ['healthy', 'disabled']);
        $queueHealthy = in_array($queue['status'], ['healthy', 'disabled', 'unknown']);

        if ($schedulerHealthy && $queueHealthy) {
            return 'healthy';
        }

        if ($scheduler['status'] === 'unhealthy' || $queue['status'] === 'unhealthy') {
            return 'unhealthy';
        }

        return 'warning';
    }
}
