<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the heartbeat monitoring system for tracking
    | the health of scheduled jobs and queue workers.
    |
    */

    // Cache key prefixes
    'cache_prefix' => 'heartbeat:',

    // Heartbeat timeout in minutes (how old before considered stale)
    'timeout' => env('HEARTBEAT_TIMEOUT', 5),

    // Scheduler heartbeat settings
    'scheduler' => [
        'enabled' => env('HEARTBEAT_SCHEDULER_ENABLED', true),
        'cache_key' => 'scheduler',
    ],

    // Queue heartbeat settings
    'queue' => [
        'enabled' => env('HEARTBEAT_QUEUE_ENABLED', true),
        'cache_key' => 'queue',
        // Dispatch heartbeat job every X jobs processed
        'frequency' => env('HEARTBEAT_QUEUE_FREQUENCY', 10),
    ],

];
