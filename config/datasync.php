<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Sync
    |--------------------------------------------------------------------------
    |
    | Pushes selected tables from THIS instance to a target instance running
    | the same iRacket codebase. Rows are sent verbatim — primary keys and all
    | foreign keys are preserved — so IDs and ownership never change.
    |
    | Roles are decided purely by env:
    |   - SENDER   (your DDEV machine): set DB_SYNC_TARGET_URL + DB_SYNC_SECRET.
    |   - RECEIVER (production):         set DB_SYNC_SECRET only (no target URL).
    |
    */

    // Production URL this instance pushes to (e.g. https://iracket.se).
    // Leave EMPTY on production so it acts only as a receiver.
    'target_url' => env('DB_SYNC_TARGET_URL'),

    // Shared secret. Must be identical on sender and receiver.
    // If empty, the receive endpoint is fully disabled (403).
    'secret' => env('DB_SYNC_SECRET'),

    // How existing rows on the target are handled:
    //   replace — delete all rows in each synced table, then insert local copy
    //             (target table becomes an EXACT MIRROR of local).
    //   merge   — insert missing rows, overwrite matching-id rows, keep the rest.
    //   insert  — only insert rows whose id is missing; never overwrite/delete.
    'mode' => env('DB_SYNC_MODE', 'replace'),

    // Rows sent per HTTP batch. Lower this if a table has very large rows
    // and you hit the target's post_max_size / max_allowed_packet limit.
    'chunk_size' => (int) env('DB_SYNC_CHUNK', 500),

    // Seconds of work done per progress-poll tick on the sender. Keeps each
    // web request short so it never times out, while progress stays live.
    'tick_budget' => (float) env('DB_SYNC_TICK_BUDGET', 4.0),

    // HTTP timeout per batch request, in seconds.
    'timeout' => (int) env('DB_SYNC_TIMEOUT', 120),

    // Framework/runtime tables that must never be synced (they are hidden from
    // the picker and rejected by the receiver). Syncing these would break
    // logins, the queue, migrations state, etc.
    'ignored_tables' => [
        'migrations',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'personal_access_tokens',
    ],

];
