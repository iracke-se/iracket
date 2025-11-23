<?php

return [
    // Page
    'scraper' => 'Scraper',
    'scraper_management' => 'Scraper Management',
    'manage_scraping' => 'Manage profixio.com data scraping',

    // Stats
    'total_runs' => 'Total Runs',
    'running' => 'Running',
    'completed' => 'Completed',
    'failed' => 'Failed',
    'unsynced_players' => 'Unsynced Players',
    'unsynced_rankings' => 'Unsynced Rankings',

    // Sync Section
    'sync_scraped_data' => 'Sync Scraped Data',
    'sync_players' => 'Sync Players',
    'sync_rankings' => 'Sync Rankings',

    // Trigger Section
    'trigger_new_scrape' => 'Trigger New Scrape',
    'type' => 'Type',
    'select_type' => 'Select type...',
    'gender' => 'Gender',
    'male' => 'Male',
    'female' => 'Female',
    'period_optional' => 'Period (optional)',
    'start_scrape' => 'Start Scrape',

    // Batch Scrape Section
    'batch_scrape' => 'Batch Scrape',
    'batch_scrape_description' => 'Run multiple scrape types at once',
    'select_types' => 'Select Types',
    'select_genders' => 'Select Genders (for Rankings)',
    'select_all' => 'Select All',
    'period' => 'Period',
    'select_period' => 'Select period...',
    'start_batch_scrape' => 'Start Batch Scrape',
    'jobs_queued' => 'jobs queued',

    // Types
    'rankings' => 'Rankings',
    'players' => 'Players',
    'transitions' => 'Transitions',
    'series' => 'Series',
    'live_center' => 'Live Center',

    // Statuses
    'pending' => 'Pending',
    'status_running' => 'Running',
    'status_completed' => 'Completed',
    'status_failed' => 'Failed',

    // Filters
    'search' => 'Search...',
    'all_types' => 'All Types',
    'all_statuses' => 'All Statuses',

    // Table Headers
    'progress' => 'Progress',
    'duration' => 'Duration',
    'started' => 'Started',
    'actions' => 'Actions',
    'scraped' => 'scraped',

    // Actions
    'view' => 'View',
    'cancel' => 'Cancel',
    'retry' => 'Retry',
    'sync' => 'Sync',
    'delete' => 'Delete',

    // Messages
    'no_runs_found' => 'No scraper runs found.',
    'confirm_cancel' => 'Are you sure you want to cancel this run?',
    'confirm_delete' => 'Are you sure you want to delete this run?',

    // Show Page
    'run_details' => 'Run Details',
    'parameters' => 'Parameters',
    'no_parameters' => 'No parameters',
    'error_message' => 'Error Message',
    'items_scraped' => 'Items Scraped',
    'items_failed' => 'Items Failed',
    'status' => 'Status',
    'logs' => 'Logs',
    'all_levels' => 'All Levels',
    'info' => 'Info',
    'warning' => 'Warning',
    'error' => 'Error',
    'no_logs_found' => 'No logs found.',
    'back' => 'Back',
    'not_started' => 'Not started',

    // Settings Page
    'settings' => 'Settings',
    'settings_title' => 'Scraper Settings',
    'settings_description' => 'Configure scraper URLs and other settings',
    'settings_saved' => 'Settings saved successfully',
    'settings_reset' => 'Settings reset to defaults',
    'url_settings' => 'URL Settings',
    'url_settings_description' => 'Configure the URLs for each scraper type. These URLs will be used when running the scrapers.',
    'url_rankings_label' => 'Rankings Scraper URL',
    'url_players_label' => 'Players List Scraper URL',
    'url_transitions_label' => 'Transitions Scraper URL',
    'url_series_label' => 'Series Scraper URL',
    'url_live_center_label' => 'Live Center Scraper URL',
    'save_settings' => 'Save Settings',
    'reset_defaults' => 'Reset to Defaults',
    'confirm_reset_defaults' => 'Are you sure you want to reset all settings to their default values?',
    'settings_help_title' => 'About Scraper URLs',
    'settings_help_text' => 'These URLs are used by the scrapers to fetch data. If the source website changes their URLs, you can update them here without modifying the code.',

    // Schedule Settings
    'schedule_settings' => 'Schedule Settings',
    'schedule_settings_description' => 'Configure when each scraper runs automatically. Enable/disable and set the frequency, day, and time for each scraper type.',
    'frequency' => 'Frequency',
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'day' => 'Day',
    'day_placeholder' => 'e.g., sunday or 1',
    'time' => 'Time',
];
