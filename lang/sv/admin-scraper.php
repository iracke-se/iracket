<?php

return [
    // Page
    'scraper' => 'Skrapare',
    'scraper_management' => 'Skraparhantering',
    'manage_scraping' => 'Hantera profixio.com dataskrapning',

    // Stats
    'total_runs' => 'Totalt körningar',
    'running' => 'Körs',
    'completed' => 'Slutförd',
    'failed' => 'Misslyckad',
    'unsynced_players' => 'Osynkade spelare',
    'unsynced_rankings' => 'Osynkade rankningar',

    // Sync Section
    'sync_scraped_data' => 'Synkronisera skrapad data',
    'sync_players' => 'Synka spelare',
    'sync_rankings' => 'Synka rankningar',

    // Trigger Section
    'trigger_new_scrape' => 'Starta ny skrapning',
    'type' => 'Typ',
    'select_type' => 'Välj typ...',
    'gender' => 'Kön',
    'male' => 'Man',
    'female' => 'Kvinna',
    'period_optional' => 'Period (valfritt)',
    'start_scrape' => 'Starta skrapning',

    // Batch Scrape Section
    'batch_scrape' => 'Batchskrapning',
    'batch_scrape_description' => 'Kör flera skrapningstyper samtidigt',
    'select_types' => 'Välj typer',
    'select_genders' => 'Välj kön (för Rankningar)',
    'select_all' => 'Välj alla',
    'period' => 'Period',
    'select_period' => 'Välj period...',
    'start_batch_scrape' => 'Starta batchskrapning',
    'jobs_queued' => 'jobb köade',

    // Types
    'rankings' => 'Rankningar',
    'players' => 'Spelare',
    'transitions' => 'Övergångar',
    'series' => 'Serier',
    'live_center' => 'Live Center',

    // Statuses
    'pending' => 'Väntande',
    'status_running' => 'Körs',
    'status_completed' => 'Slutförd',
    'status_failed' => 'Misslyckad',

    // Filters
    'search' => 'Sök...',
    'all_types' => 'Alla typer',
    'all_statuses' => 'Alla statusar',

    // Table Headers
    'progress' => 'Framsteg',
    'duration' => 'Varaktighet',
    'started' => 'Startad',
    'actions' => 'Åtgärder',
    'scraped' => 'skrapade',

    // Actions
    'view' => 'Visa',
    'cancel' => 'Avbryt',
    'retry' => 'Försök igen',
    'sync' => 'Synka',
    'delete' => 'Ta bort',

    // Messages
    'no_runs_found' => 'Inga skraparkörningar hittades.',
    'confirm_cancel' => 'Är du säker på att du vill avbryta denna körning?',
    'confirm_delete' => 'Är du säker på att du vill ta bort denna körning?',

    // Show Page
    'run_details' => 'Körningsdetaljer',
    'parameters' => 'Parametrar',
    'no_parameters' => 'Inga parametrar',
    'error_message' => 'Felmeddelande',
    'items_scraped' => 'Skrapade objekt',
    'items_failed' => 'Misslyckade objekt',
    'status' => 'Status',
    'logs' => 'Loggar',
    'all_levels' => 'Alla nivåer',
    'info' => 'Info',
    'warning' => 'Varning',
    'error' => 'Fel',
    'no_logs_found' => 'Inga loggar hittades.',
    'back' => 'Tillbaka',
    'not_started' => 'Ej startad',

    // Settings Page
    'settings' => 'Inställningar',
    'settings_title' => 'Skraparinställningar',
    'settings_description' => 'Konfigurera skrapar-URLer och andra inställningar',
    'settings_saved' => 'Inställningar sparade',
    'settings_reset' => 'Inställningar återställda till standard',
    'url_settings' => 'URL-inställningar',
    'url_settings_description' => 'Konfigurera URLer för varje skrapartyp. Dessa URLer används vid körning av skraparna.',
    'url_rankings_label' => 'Rankningsskrapar-URL',
    'url_players_label' => 'Spelarlisteskrapar-URL',
    'url_transitions_label' => 'Övergångsskrapar-URL',
    'url_series_label' => 'Serieskrapar-URL',
    'url_live_center_label' => 'Live Center-skrapar-URL',
    'save_settings' => 'Spara inställningar',
    'reset_defaults' => 'Återställ till standard',
    'confirm_reset_defaults' => 'Är du säker på att du vill återställa alla inställningar till standardvärden?',
    'settings_help_title' => 'Om skrapar-URLer',
    'settings_help_text' => 'Dessa URLer används av skraparna för att hämta data. Om källwebbplatsen ändrar sina URLer kan du uppdatera dem här utan att ändra koden.',

    // Export Schedule Settings
    'export_schedule' => 'Exportschema',
    'export_schedule_description' => 'Konfigurera när den fullständiga dataexporten körs automatiskt. Detta kör alla skrapare och sparar den kompletta datan till JSON-filer.',
    'monthly_full_export' => 'Månatlig fullständig export',
    'day_of_month' => 'Dag i månaden',
    'time' => 'Tid',
    'export_includes_all' => 'Den fullständiga exporten inkluderar alla skrapare: Spelare, Rankningar (herrar & damer), Övergångar, Serier och Live Center.',
];
