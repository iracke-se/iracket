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
        // profixio returns "403 Request forbidden by administrative rules" to the
        // default HeadlessChrome user-agent, so every browser (Browsershot + the
        // Python Playwright scripts) must identify as a regular desktop Chrome.
        'user_agent' => env('SCRAPER_USER_AGENT', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'),
        // X display a *headed* browser can open on. Only needed to obtain the
        // Cloudflare clearance (see 'cloudflare' below); on the server this is
        // the virtual display started by scripts/scraper/setup-display.sh.
        'display' => env('SCRAPER_DISPLAY', ':0'),
        'xdg_runtime_dir' => env('SCRAPER_XDG_RUNTIME_DIR', '/run/user/0'),
    ],

    /*
    | Cloudflare managed challenge
    |
    | Since 2026-09 profixio fronts ranking_sbtf_list.php and serieoppsett.php
    | with a Cloudflare managed challenge. Headless Chromium can never pass it;
    | a headed Chromium clears it in a few seconds and gets a `cf_clearance`
    | cookie bound to the server IP + user-agent. scripts/scraper/cf_clearance.py
    | obtains that cookie on a (virtual) display, and every scraper that touches
    | a challenged page sends it along, headless. See SCRAPER.md § Cloudflare.
    */
    'cloudflare' => [
        'enabled' => env('SCRAPER_CF_CLEARANCE', true),
        // A page behind the challenge — used to trigger and pass it.
        'challenge_url' => 'https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender=m',
        // Seconds to wait for the headed browser to clear the challenge.
        'timeout' => env('SCRAPER_CF_TIMEOUT', 90),
        // How long to reuse a clearance before obtaining a fresh one. The cookie
        // itself is issued for a year, but a fresh one per run is cheap.
        'cache_ttl' => env('SCRAPER_CF_CACHE_TTL', 6 * 3600),
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
        // Rankings scraper pacing. profixio sits behind Cloudflare rate limiting
        // (verified 2026-09-15): ~7 requests in 5s => HTTP 429 for minutes, while a
        // steady 1 request / 2s never trips it. Each player popup is 2 requests
        // (ranking history + matches), so one tab with a 3s pause between popups
        // stays under the limit. A full month takes ~13h at this pace.
        'concurrency' => env('SCRAPER_PYTHON_CONCURRENCY', 1),
        'popup_delay' => env('SCRAPER_PYTHON_POPUP_DELAY', 3.0),
        // Run male and female scrapes at the same time (doubles the request rate —
        // only safe if profixio lifts the rate limit).
        'parallel_genders' => env('SCRAPER_PYTHON_PARALLEL_GENDERS', false),
    ],

    // Live Center scraper settings
    'live_center' => [
        'login_url' => 'https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT',
        'livecenter_url' => 'https://www.profixio.com/fx/livecenter/',
        'callback_url' => 'https://www.profixio.com/fx/livecenter/callback.php',
        'delays' => [
            'after_page_load' => 2000,
            'after_click' => 2000,
            'after_filter_change' => 3000,
        ],
    ],
];
