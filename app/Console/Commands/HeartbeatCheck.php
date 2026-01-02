<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class HeartbeatCheck extends Command
{
    protected $signature = 'heartbeat:check {--json : Output as JSON}';

    protected $description = 'Check the health of scheduler and queue workers';

    public function handle(): int
    {
        $results = $this->checkHeartbeats();

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return $results['overall_status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
        }

        $this->displayResults($results);

        return $results['overall_status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
    }

    protected function checkHeartbeats(): array
    {
        $timeout = config('heartbeat.timeout', 5);
        $results = [
            'checked_at' => now()->toIso8601String(),
            'timeout_minutes' => $timeout,
            'scheduler' => $this->checkScheduler($timeout),
            'queue' => $this->checkQueue($timeout),
        ];

        $results['overall_status'] = ($results['scheduler']['status'] === 'healthy' && $results['queue']['status'] === 'healthy')
            ? 'healthy'
            : 'unhealthy';

        return $results;
    }

    protected function checkScheduler(int $timeout): array
    {
        if (!config('heartbeat.scheduler.enabled')) {
            return [
                'status' => 'disabled',
                'message' => 'Scheduler heartbeat monitoring is disabled',
            ];
        }

        $cacheKey = config('heartbeat.cache_prefix') . config('heartbeat.scheduler.cache_key');
        $heartbeat = Cache::get($cacheKey);

        if (!$heartbeat) {
            return [
                'status' => 'unhealthy',
                'message' => 'No scheduler heartbeat found',
                'last_beat' => null,
            ];
        }

        $lastBeat = now()->createFromTimestamp($heartbeat['unix_timestamp']);
        $minutesAgo = now()->diffInMinutes($lastBeat);

        if ($minutesAgo > $timeout) {
            return [
                'status' => 'unhealthy',
                'message' => "Scheduler heartbeat is stale ({$minutesAgo} minutes old)",
                'last_beat' => $heartbeat['timestamp'],
                'minutes_ago' => $minutesAgo,
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Scheduler is running normally',
            'last_beat' => $heartbeat['timestamp'],
            'minutes_ago' => $minutesAgo,
        ];
    }

    protected function checkQueue(int $timeout): array
    {
        if (!config('heartbeat.queue.enabled')) {
            return [
                'status' => 'disabled',
                'message' => 'Queue heartbeat monitoring is disabled',
            ];
        }

        $cacheKey = config('heartbeat.cache_prefix') . config('heartbeat.queue.cache_key');
        $heartbeat = Cache::get($cacheKey);

        if (!$heartbeat) {
            return [
                'status' => 'unknown',
                'message' => 'No queue heartbeat found yet (queue may be idle or not processing jobs)',
                'last_beat' => null,
            ];
        }

        $lastBeat = now()->createFromTimestamp($heartbeat['unix_timestamp']);
        $minutesAgo = now()->diffInMinutes($lastBeat);

        if ($minutesAgo > $timeout) {
            return [
                'status' => 'unhealthy',
                'message' => "Queue heartbeat is stale ({$minutesAgo} minutes old)",
                'last_beat' => $heartbeat['timestamp'],
                'minutes_ago' => $minutesAgo,
                'queue' => $heartbeat['queue'] ?? 'unknown',
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Queue worker is running normally',
            'last_beat' => $heartbeat['timestamp'],
            'minutes_ago' => $minutesAgo,
            'queue' => $heartbeat['queue'] ?? 'unknown',
        ];
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('=== Heartbeat Health Check ===');
        $this->newLine();

        // Scheduler status
        $this->line('📅 <fg=cyan>Scheduler:</>');
        $this->displayStatus($results['scheduler']);
        $this->newLine();

        // Queue status
        $this->line('⚙️  <fg=cyan>Queue Worker:</>');
        $this->displayStatus($results['queue']);
        $this->newLine();

        // Overall status
        $overallColor = $results['overall_status'] === 'healthy' ? 'green' : 'red';
        $overallIcon = $results['overall_status'] === 'healthy' ? '✓' : '✗';
        $this->line("<fg={$overallColor}>{$overallIcon} Overall Status: {$results['overall_status']}</>");
        $this->newLine();
    }

    protected function displayStatus(array $status): void
    {
        $color = match($status['status']) {
            'healthy' => 'green',
            'unhealthy' => 'red',
            'disabled' => 'yellow',
            'unknown' => 'yellow',
            default => 'white',
        };

        $icon = match($status['status']) {
            'healthy' => '✓',
            'unhealthy' => '✗',
            'disabled' => '○',
            'unknown' => '?',
            default => '•',
        };

        $this->line("  <fg={$color}>{$icon} Status: {$status['status']}</>");
        $this->line("  Message: {$status['message']}");

        if (isset($status['last_beat'])) {
            $this->line("  Last Beat: {$status['last_beat']} ({$status['minutes_ago']} min ago)");
        }

        if (isset($status['queue'])) {
            $this->line("  Queue: {$status['queue']}");
        }
    }
}
