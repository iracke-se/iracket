# Scraper Smoke Tests

A step-by-step playbook for validating every part of the scraper pipeline on a fresh server, in increasing order of cost and fragility. Each test has: the command, what success looks like, how to verify, and the common failure modes with fixes.

See [SCRAPER.md](../SCRAPER.md) for the architectural reference.

All commands assume you're in the Laravel root:

```bash
cd /www/wwwroot/iracket.se
```

---

## 0. Pre-flight

Before any smoke test, confirm environment + operational state are clean:

```bash
php artisan scraper:check     # PHP/Node/npm/Chromium/DB/Browsershot
php artisan scraper:health    # queue worker, scheduler, Redis, profixio, disk
```

Both should report only the expected warnings ("Last successful runs: never" and "No runs in last 7 days" are normal on a fresh deploy — they clear after the first successful run).

If `scraper:health` shows failures, each line has a `↳ Fix:` hint underneath — follow that first.

---

## 1. Live Center smoke (cheapest, no browser)

`LiveCenterDetailsScraper` runs a Python stdlib HTTP script (`livecenter_scraper.py`) — no Chromium, no Playwright. Confirms: Python binary, queue worker, DB connection, profixio reachability.

```bash
# Today's matches
php artisan scraper:run live_center --date=$(date +%Y-%m-%d) --queue

# Or a known-busy past date if today has no matches
# php artisan scraper:run live_center --date=2026-05-20 --queue

# In another terminal — watch the worker
tail -f /www/wwwroot/iracket.se/storage/logs/scraper-worker.log
```

**Success looks like:**
```
App\Jobs\Scraper\ScrapeLiveCenterJob ........... RUNNING
App\Jobs\Scraper\ScrapeLiveCenterJob ..... 635ms DONE
```

**Verify:**
```bash
php artisan tinker --execute="
\$r = \App\Models\Scraper\ScraperRun::where('type','live_center')->latest()->first();
echo 'run #'.\$r->id.' status='.\$r->status.' items='.\$r->items_scraped.PHP_EOL;
"
```

`status=completed` is the pass condition. `items_scraped=0` is fine if the date had no matches — try a different `--date` to confirm.

**Typical duration:** 0.5–2 seconds. Anything faster than 200ms with no items = failed precondition; check `error_message`.

**Common failures:**

| Symptom | Cause | Fix |
|---|---|---|
| `ModuleNotFoundError: urllib` | Wrong Python binary | Fix `SCRAPER_PYTHON_BINARY` in `.env` |
| `Connection refused` | profixio unreachable from server | Check outbound HTTPS; rerun `scraper:health` |
| DONE in 50ms with items=0 | Date has no matches | Try `--date=<recent past date>` |

---

## 2. Players smoke (pure PHP HTTP)

`PlayerListScraper` uses Guzzle — no browser, no Python. Confirms: HTTP client, club roster parsing, scraped_players writes.

```bash
# Smallest viable: scrape only the latest period
php artisan scraper:run players --period=$(date +%Y).$(date +%m).01 --direction=gte --queue
```

**Verify:**
```bash
php artisan tinker --execute="
\$r = \App\Models\Scraper\ScraperRun::where('type','players')->latest()->first();
echo 'run #'.\$r->id.' status='.\$r->status.' items='.\$r->items_scraped.PHP_EOL;
echo 'new scraped_players rows: '.\App\Models\Scraper\ScrapedPlayer::where('scraper_run_id',\$r->id)->count().PHP_EOL;
"
```

**Typical duration:** 1–10 minutes depending on club count.

**Common failures:**

| Symptom | Cause | Fix |
|---|---|---|
| HTTP 4xx from profixio | URL changed or rate-limited | Verify `SCRAPER_MAIN_URL` in `.env`; check from `scraper:health` |
| Memory exhausted | Too many clubs at once | Raise `memory_limit` in `php.ini` or split with `--limit-clubs` |

---

## 3. Transitions smoke (Browsershot path)

`TransitionsScraper` uses Spatie Browsershot → headless Chromium. **First test that exercises Node + npm + system Chromium.** This is the path used by Transitions, Series, and Series Matches scrapers.

```bash
php artisan scraper:run transitions --queue --limit-periods=1
```

**Verify:**
```bash
php artisan tinker --execute="
\$r = \App\Models\Scraper\ScraperRun::where('type','transitions')->latest()->first();
echo 'run #'.\$r->id.' status='.\$r->status.' items='.\$r->items_scraped.PHP_EOL;
"
```

**Typical duration:** 1–5 minutes.

**Common failures:**

| Symptom | Cause | Fix |
|---|---|---|
| `Chrome failed to launch: Failed to launch the browser process` | `SCRAPER_CHROME_PATH` invalid | `which chromium-browser google-chrome`; update `.env`; `php artisan config:cache` |
| `error while loading shared libraries: libnss3.so` | Chromium system deps missing | RHEL: `dnf install nss atk at-spi2-atk cups-libs libdrm libxkbcommon libXcomposite libXrandr mesa-libgbm alsa-lib pango libXScrnSaver` — Debian: `apt-get install libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 libxrandr2 libgbm1 libasound2` |
| `node: command not found` | Wrong path | `which node`; update `SCRAPER_NODE_BINARY` in `.env` |
| Timeout after 60s | profixio slow OR Chromium sandbox issue | Re-run; if persistent, add `--no-sandbox` flag (already set in BrowserService) |

---

## 4. Series smoke (Browsershot, longer)

Same tech stack as Transitions. Tests pagination + season discovery.

```bash
php artisan scraper:run series --queue --limit-seasons=1
```

**Typical duration:** 2–10 minutes.

Same failure modes as Smoke 3.

---

## 5. Rankings smoke (Python Playwright — the heaviest)

`RankingsScraper` runs `scripts/scraper/rankings_popup_scraper.py` which uses Python Playwright to open Chromium and click each player's popup for match history. **This is the most fragile path** because it depends on:
- Python `playwright` package installed in the venv
- Playwright's bundled Chromium downloaded (`playwright install chromium`)
- All Chromium system deps available (same as Smoke 3)
- Profixio's HTML structure unchanged

```bash
# Smallest atomic unit: one month, one gender
php artisan scraper:run rankings --year=2026 --month=04 --gender=m --queue
```

**Typical duration:** 30 minutes – 2 hours depending on how many players are ranked that month. The default concurrency is 10 parallel tabs.

**Mid-run progress:**
```bash
php artisan tinker --execute="
\$r = \App\Models\Scraper\ScraperRun::where('type','rankings')->latest()->first();
echo 'run #'.\$r->id.' status='.\$r->status.' step='.(\$r->current_step ?: 'n/a').' items='.\$r->items_scraped.' failed='.\$r->items_failed.PHP_EOL;
"
```

**Verify after completion:**
```bash
php artisan tinker --execute="
\$r = \App\Models\Scraper\ScraperRun::where('type','rankings')->latest()->first();
echo 'scraped_rankings rows: '.\App\Models\Scraper\ScrapedRanking::where('scraper_run_id',\$r->id)->count().PHP_EOL;
echo 'scraped_matches rows: '.\App\Models\Scraper\ScrapedMatch::where('scraper_run_id',\$r->id)->count().PHP_EOL;
"
```

**Common failures:**

| Symptom | Cause | Fix |
|---|---|---|
| `ModuleNotFoundError: No module named 'playwright'` | Playwright not installed in venv | `cd scripts/scraper && source venv/bin/activate && pip install playwright && playwright install chromium && deactivate` |
| `Executable doesn't exist at /root/.cache/ms-playwright/chromium-*/chrome-linux/chrome` | Bundled Chromium not downloaded | Same as above (`playwright install chromium`) |
| `Failed to launch chromium because: shared library not found` | Chromium system deps missing | Install per Smoke 3 OR point `SCRAPER_CHROME_PATH` at system Chromium and pass `--executable-path` (not currently supported in script — install deps instead) |
| `Timeout 30000ms exceeded waiting for selector` | Profixio HTML changed | Inspect `rankings_popup_scraper.py` — find the selector + update; report upstream |
| Job fails in <500ms | Precondition error | Check `error_message` (commands below) |

---

## 6. Sync services smoke

After Smokes 1–5 leave raw rows in the `scraped_*` tables, verify the promotion pipeline.

```bash
# Show what WOULD sync — no DB writes
php artisan scraper:sync all --dry-run

# Promote everything
php artisan scraper:sync all
```

**Verify:**
```bash
php artisan tinker --execute="
foreach (['ScrapedPlayer','ScrapedRanking','ScrapedMatch','ScrapedTransition'] as \$m) {
    \$class = 'App\\\\Models\\\\Scraper\\\\'.\$m;
    echo \$m.' unsynced: '.\$class::where('is_synced',false)->count().PHP_EOL;
}
"
```

All counts should be 0 (or low — leftover rows are ones with unmatchable names/clubs; check `scraper_logs` level=error for details).

**Common failures:**

| Symptom | Cause | Fix |
|---|---|---|
| `Integrity constraint violation: 1062 Duplicate entry` | Unique key collision | Inspect the colliding row; likely needs natural-key dedup |
| Lots of "skipped: name not found" | Name format mismatch with `users` table | Expected for first-ever sync; resolves as more players are scraped |
| `Foreign key constraint fails` | Club doesn't exist on prod yet | Ensure players domain is synced before transitions |

---

## 7. Full orchestrator smoke (scraper:start)

The 15-step "do everything for one month" workflow. Tests the integration between all the above.

```bash
# Smoke variant — skip the longest steps and the backup
php artisan scraper:start \
  --skip-live-center \
  --skip-series \
  --skip-transitions \
  --no-backup

# (Defaults month to last month when omitted.)
```

The orchestrator will run, in order: backup (skipped) → rankings (M, F) → players → sync players → sync rankings → monthly_rankings → sync matches → series (skipped) → transitions (skipped) → live center (skipped) → verify.

**Typical duration:** 1–3 hours.

If this succeeds end-to-end, the scheduled monthly run (first Tuesday at 02:00) will too.

**To run the *real* full version** (every step enabled), drop the `--skip-*` flags. That's 4–6 hours.

**Common failures:** the orchestrator fails at the first broken step and tells you which one. Diagnose that step using its individual smoke test above.

---

## 8. Backfill smoke

```bash
# Print the plan without dispatching anything
php artisan scraper:backfill --dry-run

# Tiny real backfill — one month, one domain, one gender — confirms machinery
php artisan scraper:backfill --from=2026-04 --to=2026-04 --domains=rankings --genders=m --force

# Then promote what got scraped
php artisan scraper:sync rankings
```

**Verify:**
```bash
php artisan tinker --execute="
\$r = \App\Models\Scraper\ScraperRun::where('type','backfill')->latest()->first();
echo 'backfill run #'.\$r->id.' status='.\$r->status.' items='.\$r->items_scraped.' failed='.\$r->items_failed.PHP_EOL;
echo 'atoms tracked:'.PHP_EOL;
foreach ((\$r->steps_data ?? []) as \$k=>\$v) echo '  '.\$k.' => '.(\$v['status'] ?? '?').PHP_EOL;
"
```

**Common failures:**

| Symptom | Cause | Fix |
|---|---|---|
| `Duplicate-name pre-flight failed` | `users` has same `(first_name, last_name)` for >1 record | Dedup the table, OR pass `--allow-name-collisions` (accepts that sync will merge data) |
| `Insufficient disk space: NGB free, recommend NGB` | Disk too tight for estimated raw-table growth | Free space (`scraper:cleanup-logs`, prune old runs) or expand disk |
| `Another backfill or full_scrape ScraperRun is currently running` | Concurrency lock | Wait for it to finish, OR `scraper:cleanup --older-than=120` to clear stuck ones, OR pass `--resume` to continue |

---

## When a smoke test fails — universal diagnostics

Run all four whenever any test fails:

```bash
# 1. Last run's high-level error
php artisan tinker --execute="
\$r = \App\Models\Scraper\ScraperRun::latest()->first();
echo 'type: '.\$r->type.PHP_EOL;
echo 'status: '.\$r->status.PHP_EOL;
echo 'error: '.\$r->error_message.PHP_EOL;
echo 'params: '.json_encode(\$r->parameters).PHP_EOL;
"

# 2. Detailed error-level scraper logs for that run
php artisan tinker --execute="
\$id = \App\Models\Scraper\ScraperRun::latest()->first()->id;
\App\Models\Scraper\ScraperLog::where('scraper_run_id',\$id)->where('level','error')
    ->latest()->limit(20)->get(['message','context'])
    ->each(fn(\$l)=>print_r(\$l->toArray()));
"

# 3. Worker log (last 100 lines)
tail -100 /www/wwwroot/iracket.se/storage/logs/scraper-worker.log

# 4. Operational state — fix hints under each red/yellow line
php artisan scraper:health
```

---

## Queue / runaway-job hygiene

If the queue gets jammed with failing/retrying jobs (e.g. from old tests):

```bash
# Wipe all failed jobs (Laravel's failed_jobs table)
php artisan queue:flush

# Clear pending jobs on the scraper queue (Redis)
php artisan queue:clear redis --queue=scraper

# Mark scraper runs stuck >2h as failed (so the lock releases)
php artisan scraper:cleanup --older-than=120

# Verify nothing left
php artisan scraper:health | grep -E "Queued|Failed|Stuck"
```

---

## Recommended order for a fresh server

```
0. scraper:check  + scraper:health      # environment + ops
1. live_center smoke                    # confirms Python + queue + DB
2. players smoke                        # confirms HTTP + sync
3. transitions smoke (--limit-periods=1) # confirms Browsershot
4. series smoke (--limit-seasons=1)     # confirms longer Browsershot
5. rankings smoke (one month, m)        # confirms Playwright (the hard one)
6. sync all                             # confirms promotion to prod tables
7. scraper:start with --skip flags      # full orchestrator
8. scraper:backfill --dry-run           # confirms plan-building
9. scraper:backfill (tiny window)       # confirms backfill machinery
10. scraper:backfill --force            # the real 5-year backfill
```

Steps 1–6 should each take <30 min total to verify. Step 7 takes 1–3h. Steps 9–10 are the production-readiness gates.
