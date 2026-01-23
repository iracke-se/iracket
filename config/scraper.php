<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scraper Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the profixio.com web scraper
    |
    */

    // Main URL for profixio.com
    'main_url' => env('SCRAPER_MAIN_URL', 'https://www.profixio.com/fx/sbtf/'),

    // Browser settings
    'browser' => [
        'headless' => env('SCRAPER_HEADLESS', true),
        'node_binary' => env('SCRAPER_NODE_BINARY', '/usr/bin/node'),
        'npm_binary' => env('SCRAPER_NPM_BINARY', '/usr/bin/npm'),
        'chrome_path' => env('SCRAPER_CHROME_PATH', null),
        'timeout' => env('SCRAPER_TIMEOUT', 60000), // 60 seconds
        'wait_until_network_idle' => true,
    ],

    // Retry settings
    'retry' => [
        'max_attempts' => 3,
        'delay_ms' => 1000,
        'backoff' => [60, 300, 600], // seconds between retries
    ],

    // Delay settings (to avoid overwhelming the server)
    'delays' => [
        'between_requests' => 300, // milliseconds
        'between_pages' => 500,
        'after_click' => 300,
        'after_select' => 500,
    ],

    // CSS Selectors for profixio.com navigation
    'selectors' => [
        'login_page' => '#main-col > div.maincontent > div:nth-child(1) > div.l-sm-8.col-md-8 > div > table > tbody > tr > td > table > tbody > tr:nth-child(2) > td:nth-child(3) > a',
        'player_list' => '#hoved-meny > li:nth-child(2) > a',
        'series' => '#hoved-meny > li:nth-child(3) > a',
        'rankings' => '#hoved-meny > li:nth-child(4) > a',
        'live_center' => '#hoved-meny > li:nth-child(5) > a',
    ],

    // Queue settings
    'queue' => [
        'connection' => env('SCRAPER_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('SCRAPER_QUEUE_NAME', 'scraper'),
    ],

    // Scrape detailed match data (increases scraping time significantly)
    'scrape_match_details' => env('SCRAPER_MATCH_DETAILS', false),

    // Parallel processing
    'parallel' => [
        'enabled' => env('SCRAPER_PARALLEL', false),
        'max_instances' => env('SCRAPER_MAX_INSTANCES', 4),
    ],

    // Batch processing - number of clubs to scrape in parallel per batch
    'batch_size' => env('SCRAPER_BATCH_SIZE', 5),

    // Logging
    'logging' => [
        'channel' => env('SCRAPER_LOG_CHANNEL', 'scraper'),
        'detailed' => env('SCRAPER_DETAILED_LOG', true),
    ],

    // Schedule settings
    'schedule' => [
        'rankings' => [
            'enabled' => env('SCRAPER_SCHEDULE_RANKINGS', true),
            'frequency' => 'weekly', // daily, weekly, monthly
            'day' => 'sunday',
            'time' => '02:00',
        ],
        'players' => [
            'enabled' => env('SCRAPER_SCHEDULE_PLAYERS', true),
            'frequency' => 'monthly',
            'day' => 1, // day of month
            'time' => '03:00',
        ],
        'series' => [
            'enabled' => env('SCRAPER_SCHEDULE_SERIES', false),
            'frequency' => 'weekly',
            'day' => 'monday',
            'time' => '04:00',
        ],
        'live_center' => [
            'enabled' => env('SCRAPER_SCHEDULE_LIVECENTER', false),
            'frequency' => 'daily',
            'time' => '05:00',
        ],
    ],

    // Python scraper settings (for Playwright-based scrapers)
    'python' => [
        'binary' => env('SCRAPER_PYTHON_BINARY', 'python3'),
        'timeout' => env('SCRAPER_PYTHON_TIMEOUT', 3600), // 1 hour default
    ],
];
