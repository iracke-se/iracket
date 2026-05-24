# iRacket Scraper

This is the canonical reference for the iRacket scraper system. It documents every moving part: services, queue jobs, artisan commands, Python scripts, database tables, scheduling, and operations.

The scraper pulls data from [profixio.com](https://www.profixio.com/fx/sbtf/) (the Swedish Table Tennis Federation's tournament platform) and stores it in `scraped_*` tables. A separate sync stage promotes validated rows into the production tables that the app reads from.

**Last updated:** 2026-05-24

---

## Table of contents

1. [Architecture overview](#architecture-overview)
2. [Data domains and flow](#data-domains-and-flow)
3. [Components inventory](#components-inventory)
4. [Database schema](#database-schema)
5. [Commands reference](#commands-reference)
6. [Scheduling](#scheduling)
7. [Configuration](#configuration)
8. [Operations](#operations)
9. [Production setup](#production-setup)
10. [Known gaps and gotchas](#known-gaps-and-gotchas)
11. [Glossary](#glossary)

---

## Architecture overview

```
┌──────────────────────────────────────────────────────────────────┐
│ Scheduler (routes/console.php)                                    │
│   - First Tuesday of month at 02:00 → scraper:start <last-month>  │
│   - Per-domain jobs (rankings, players, etc.) — DB-settings gated │
└──────────────────────┬───────────────────────────────────────────┘
                       ↓
┌──────────────────────────────────────────────────────────────────┐
│ Artisan commands (app/Console/Commands/)                          │
│   scraper:start  scraper:run  scraper:queue                       │
│   scraper:sync   scraper:export  scraper:check                    │
│   scraper:cleanup  scraper:cleanup-logs                           │
└──────────────────────┬───────────────────────────────────────────┘
                       ↓
┌──────────────────────────────────────────────────────────────────┐
│ Queue jobs (app/Jobs/Scraper/)  → "scraper" queue (Redis on prod) │
│   ScrapeRankingsJob, ScrapePlayersJob, ScrapeTransitionsJob,      │
│   ScrapeSeriesJob, ScrapeLiveCenterJob, RunScraperJob             │
└──────────────────────┬───────────────────────────────────────────┘
                       ↓
┌──────────────────────────────────────────────────────────────────┐
│ Scraper services (app/Services/Scraper/)                          │
│                                                                    │
│  Browser-based (Spatie Browsershot + headless Chrome):            │
│    SeriesScraper        — series standings                        │
│    SeriesMatchScraper   — series match results                    │
│    TransitionsScraper   — club transfers                          │
│                                                                    │
│  Python Playwright (scripts/scraper/rankings_popup_scraper.py):   │
│    RankingsScraper      — monthly rankings + popup match history  │
│                                                                    │
│  Python stdlib HTTP (scripts/scraper/livecenter_scraper.py):      │
│    LiveCenterDetailsScraper  — team match / game / set / point    │
│                                                                    │
│  Pure PHP HTTP (Guzzle):                                          │
│    PlayerListScraper    — club roster scraping                    │
└──────────────────────┬───────────────────────────────────────────┘
                       ↓ (writes raw rows with is_synced=false)
┌──────────────────────────────────────────────────────────────────┐
│ Raw tables                                                        │
│   scraped_players / scraped_rankings / scraped_matches            │
│   scraped_transitions / scraped_standings                         │
│   live_match_details / live_match_games / live_match_sets         │
│   live_match_points                                               │
└──────────────────────┬───────────────────────────────────────────┘
                       ↓ (sync services)
┌──────────────────────────────────────────────────────────────────┐
│ Sync services (app/Services/Scraper/*SyncService.php)             │
│   SyncService          — players + rankings                       │
│   MatchSyncService     — matches                                  │
│   LiveCenterSyncService — live games (singles only)               │
│   [planned] TransitionSyncService, StandingsSyncService           │
└──────────────────────┬───────────────────────────────────────────┘
                       ↓ (writes to)
┌──────────────────────────────────────────────────────────────────┐
│ Production tables — what the app actually reads                   │
│   users / clubs / matches / monthly_rankings                      │
│   club_monthly_rankings / club_transitions                        │
│   [planned] club_standings                                        │
└──────────────────────────────────────────────────────────────────┘
```

### Logging

Every scrape run gets a `ScraperRun` row tracking status, parameters, items_scraped, items_failed, and start/end timestamps. The model exposes `info()`, `warning()`, `error()` helpers that write to `ScraperLog` rows (DB) AND the `scraper` Laravel log channel (filesystem under `storage/scraper_logs/`). This is the **dual logging** pattern — DB for UI/queries, filesystem for audit trail.

---

## Data domains and flow

The scraper handles six domains. For each: where it comes from, which service does the work, what tables it lands in, and how it's promoted to production.

### 1. Rankings + popup matches

- **Entry**: `scraper:run rankings --year YYYY --month MM --gender m|k` or `scraper:start <month>`
- **Service**: [RankingsScraper](app/Services/Scraper/RankingsScraper.php) → runs Python script
- **Python**: [scripts/scraper/rankings_popup_scraper.py](scripts/scraper/rankings_popup_scraper.py) — Playwright-based; opens the ranking page, paginates, and clicks each player's name to extract match history from a popup
- **Source URL**: `https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender={m|k}&rid={rid}&from={offset}`
- **Concurrency**: default 10 parallel tabs (configurable via `--concurrency N`)
- **Raw tables**: `scraped_rankings`, `scraped_matches`
- **Sync**: [SyncService::syncRankings()](app/Services/Scraper/SyncService.php) + [MatchSyncService](app/Services/Scraper/MatchSyncService.php)
- **Production**: `users` (created/matched by name), `monthly_rankings`, `matches`
- **Atom (smallest unit)**: one (year, month, gender) tuple
- **Idempotent**: yes — `is_synced` flag prevents re-promotion; `synced_ranking_id` tracks the production row

### 2. Players (club rosters)

- **Entry**: `scraper:run players [--period YYYY.MM.DD] [--direction gte|lte]` or `scraper:start <month>`
- **Service**: [PlayerListScraper](app/Services/Scraper/PlayerListScraper.php) — pure PHP HTTP client, no browser
- **Source URL**: `https://www.profixio.com/fx/lisens/public_oversikt.php` (guest session via `login_public=SBTF.SE.BT`)
- **Raw table**: `scraped_players`
- **Sync**: [SyncService::syncPlayers()](app/Services/Scraper/SyncService.php) — creates `users` row with synthesized email if not present, links to `clubs.slug`
- **Production**: `users`, `clubs`
- **Atom**: one period (YYYY.MM.DD format from the profixio dropdown)
- **Idempotent**: partial — upserts on synthesized email; name collisions silently merge to the same user

### 3. Matches (series-based)

- **Entry**: `scraper:run series_matches` or `scraper:start <month>` step
- **Service**: [SeriesMatchScraper](app/Services/Scraper/SeriesMatchScraper.php) — Browsershot
- **Source URL**: `https://www.profixio.com/fx/serieoppsett.php`
- **Raw table**: `scraped_matches` (with `source=series`)
- **Sync**: [MatchSyncService::syncMatches()](app/Services/Scraper/MatchSyncService.php)
- **Production**: `matches`
- **Atom**: one season
- **Idempotent**: partial — matches identified by `(player1_name, player2_name, played_at)`

### 4. Series standings (team standings)

- **Entry**: `scraper:run series` or `scraper:start <month>` step
- **Service**: [SeriesScraper](app/Services/Scraper/SeriesScraper.php) — Browsershot
- **Source URL**: `https://www.profixio.com/fx/serieoppsett.php` (discovers seasons → series → standings table)
- **Raw table**: `scraped_standings`
- **Sync**: [StandingsSyncService::syncStandings()](app/Services/Scraper/StandingsSyncService.php) — upserts into `club_standings` keyed by `(team_name, series_name, session_name)`
- **Production**: `club_standings` (new table as of 2026-05-24)
- **Atom**: one season

### 5. Club transitions

- **Entry**: `scraper:run transitions` or `scraper:start <month>` step
- **Service**: [TransitionsScraper](app/Services/Scraper/TransitionsScraper.php) — Browsershot
- **Source URL**: `https://www.profixio.com/fx/lisens/public_overgang.php`
- **Raw table**: `scraped_transitions`
- **Sync**: [TransitionSyncService::syncTransitions()](app/Services/Scraper/TransitionSyncService.php) — matches player by `(first_name, last_name)`, looks up `from_club`/`to_club` by name, upserts into `club_transitions`
- **Production**: `club_transitions`
- **Atom**: one period

### 6. Live center (team match details with set/point data)

- **Entry**: `scraper:run live_center [--date YYYY-MM-DD | --month YYYY-MM | --year YYYY] [--skip-points]` or `scraper:start <month>` step
- **Service**: [LiveCenterDetailsScraper](app/Services/Scraper/LiveCenterDetailsScraper.php) → runs Python script
- **Python**: [scripts/scraper/livecenter_scraper.py](scripts/scraper/livecenter_scraper.py) — **stdlib HTTP only, no Chromium needed**. Hits `callback.php` JSON API directly with `urllib` + `http.cookiejar`.
- **Source URL**: `https://www.profixio.com/fx/livecenter/callback.php` (POST API)
- **Raw tables**: `live_match_details`, `live_match_games`, `live_match_sets`, `live_match_points`
- **Sync**: [LiveCenterSyncService::syncMatches()](app/Services/Scraper/LiveCenterSyncService.php)
- **Production**: `matches` (linked via `live_match_game_id`)
- **Atom**: one date — but `--month` and `--year` are also supported by looping internally
- **Idempotent**: yes — duplicate detection on `(team1_name, team2_name, played_at)`
- **Sync rules** (important):
  - **Singles only** — doubles games (D1, D2) are marked `is_synced=true` but never written to `matches`
  - **Only when both players exist in `users`** — otherwise marked `is_synced=true` and skipped (does not block subsequent runs)

---

## Components inventory

### Services (`app/Services/Scraper/`)

| Class | Role | Tech |
|---|---|---|
| [BaseScraperService.php](app/Services/Scraper/BaseScraperService.php) | Abstract base — run lifecycle, retry, logging | — |
| [BrowserService.php](app/Services/Scraper/BrowserService.php) | Browsershot factory (node/npm/chrome path config) | — |
| [RankingsScraper.php](app/Services/Scraper/RankingsScraper.php) | Rankings + popup matches | Python Playwright |
| [PlayerListScraper.php](app/Services/Scraper/PlayerListScraper.php) | Club rosters | PHP HTTP |
| [SeriesMatchScraper.php](app/Services/Scraper/SeriesMatchScraper.php) | Series match results | Browsershot |
| [SeriesScraper.php](app/Services/Scraper/SeriesScraper.php) | Series standings | Browsershot |
| [TransitionsScraper.php](app/Services/Scraper/TransitionsScraper.php) | Club transfers | Browsershot |
| [LiveCenterDetailsScraper.php](app/Services/Scraper/LiveCenterDetailsScraper.php) | Team match games/sets/points | Python stdlib HTTP |
| [SyncService.php](app/Services/Scraper/SyncService.php) | Promote players + rankings | — |
| [MatchSyncService.php](app/Services/Scraper/MatchSyncService.php) | Promote matches | — |
| [LiveCenterSyncService.php](app/Services/Scraper/LiveCenterSyncService.php) | Promote live-center games (singles, both players exist) | — |
| [TransitionSyncService.php](app/Services/Scraper/TransitionSyncService.php) | Promote club transitions | — |
| [StandingsSyncService.php](app/Services/Scraper/StandingsSyncService.php) | Promote series standings | — |
| [ScraperExporter.php](app/Services/Scraper/ScraperExporter.php) | JSON archive of all scraped data | — |

### Queue jobs (`app/Jobs/Scraper/`)

| Job | Timeout | Tries |
|---|---|---|
| [ScrapeRankingsJob.php](app/Jobs/Scraper/ScrapeRankingsJob.php) | unlimited (0) | 3 |
| [ScrapePlayersJob.php](app/Jobs/Scraper/ScrapePlayersJob.php) | 2h | 3 |
| [ScrapeTransitionsJob.php](app/Jobs/Scraper/ScrapeTransitionsJob.php) | 1h | 3 |
| [ScrapeSeriesJob.php](app/Jobs/Scraper/ScrapeSeriesJob.php) | 2h | 3 |
| [ScrapeLiveCenterJob.php](app/Jobs/Scraper/ScrapeLiveCenterJob.php) | 3h | 3 |
| [RunScraperJob.php](app/Jobs/Scraper/RunScraperJob.php) | — | — |

All go on the `scraper` queue. Connection is `redis` in production (configured via `SCRAPER_QUEUE_CONNECTION` env var; defaults to `database`).

### Python scripts (`scripts/scraper/`)

| Script | Tech | Args |
|---|---|---|
| [rankings_popup_scraper.py](scripts/scraper/rankings_popup_scraper.py) | Playwright async + Chromium | `--year YYYY --month MM --gender m\|k [--limit N] [--concurrency N]` |
| [livecenter_scraper.py](scripts/scraper/livecenter_scraper.py) | stdlib urllib + http.cookiejar | `--date YYYY-MM-DD \| --month YYYY-MM \| --year YYYY [--limit-matches N] [--skip-points]` |

### Models (`app/Models/Scraper/` + a few in `app/Models/`)

| Model | Tracks |
|---|---|
| ScraperRun | One execution; status, type, parameters, steps_data, counters |
| ScraperLog | Detailed log lines per run (level, message, context json) |
| ScrapedPlayer | Raw player roster row |
| ScrapedRanking | Raw ranking row + match history |
| ScrapedMatch | Raw match row (from popup or series) |
| ScrapedTransition | Raw club transfer row |
| ScrapedStanding | Raw series standing row |
| LiveMatchDetail | Team match metadata |
| LiveMatchGame | Game within a team match (S1/S2/D1/etc.) |
| LiveMatchSet | Set within a game |
| LiveMatchPoint | Point within a set |
| ScraperSetting | DB-backed settings (schedule enable/freq/day/time, URLs) |

`ScraperRun.type` values: `rankings`, `players`, `transitions`, `series`, `series_matches`, `live_center`, `full_scrape`, `backfill`.

---

## Database schema

### Raw tables (`scraped_*`, `live_match_*`)

All raw tables follow the pattern:
- `scraper_run_id` foreign key → ties row to a `ScraperRun`
- `is_synced` boolean — set to `true` once promoted (or once decided not to promote)
- `synced_*_id` nullable foreign key → tracks which production row this raw row created

### Production tables (what the app reads)

| Table | Source | Notes |
|---|---|---|
| `users` | scraped_players, scraped_rankings | UUID routing; matched by name |
| `clubs` | scraped_players | Slug routing |
| `matches` | scraped_matches, live_match_games | UUID routing; linked to live games via `live_match_game_id` |
| `monthly_rankings` | scraped_rankings | One row per (user, division, gender, month) |
| `club_monthly_rankings` | aggregated | Monthly club ranking snapshot (different from series standings) |
| `club_transitions` | scraped_transitions | Synced via TransitionSyncService |
| `club_standings` | scraped_standings | Synced via StandingsSyncService (table created 2026-05-24) |

---

## Commands reference

All commands are under `app/Console/Commands/`. Use `php artisan <cmd> --help` for full options.

### `scraper:start {month}`

The **orchestrator** — runs the 11-step "do everything for one month" workflow:

1. Backup database (skippable with `--no-backup`)
2. Scrape rankings (male) — Python popup scraper
3. Scrape rankings (female)
4. Scrape players (rosters)
5. Sync players → users
6. Sync rankings → users + monthly_rankings
7. Create monthly rankings aggregation
8. Sync matches (from popup + series)
9. Scrape series standings (skippable with `--skip-series`)
10. Scrape live center (skippable with `--skip-live-center`)
11. Sync live center games → matches
12. Verify counts

Creates a `ScraperRun` with `type='full_scrape'` tracking the whole multi-step workflow in `steps_data`.

```bash
php artisan scraper:start 2026-04
php artisan scraper:start 2026-04 --no-backup --skip-live-center
```

### `scraper:run {type}`

Low-level — runs one domain.

| Type | Required args |
|---|---|
| `rankings` | `--year YYYY --month MM --gender m\|k` |
| `players` | `[--period YYYY.MM.DD] [--direction gte\|lte]` |
| `transitions` | none (scrapes all periods) |
| `series` | `[--limit-seasons N] [--limit-series N]` |
| `series_matches` | none |
| `live_center` | `[--date YYYY-MM-DD] [--month YYYY-MM] [--year YYYY] [--skip-points]` |

Add `--queue` to dispatch async onto the scraper queue. Default is synchronous.

### `scraper:queue {type} [month]`

Dispatch `scraper:start` or `scraper:run` to the background queue. Checks for already-running scrapers first.

### `scraper:backfill [--from=YYYY-MM] [--to=YYYY-MM] [--domains=...] [--genders=m,k] [--dry-run] [--resume] [--sync]`

Historical backfill across all six domains. Default range is the last five years through the previous month, queueing one atom per (year, month, gender) for rankings + one per year for live center + one each for players/transitions/series.

```bash
# Preview what would run (no dispatching)
php artisan scraper:backfill --dry-run

# Backfill 2021-01 through last month — default, queue mode
php artisan scraper:backfill --force

# Backfill only the rankings domain for a specific window
php artisan scraper:backfill --from=2023-01 --to=2023-12 --domains=rankings

# Resume the latest interrupted backfill
php artisan scraper:backfill --resume --force
```

Pre-flight safety:
- Refuses to start if another `full_scrape` or `backfill` is in `running` state
- Aborts on duplicate `(first_name, last_name)` pairs in `users` unless `--allow-name-collisions` is passed (because sync would silently merge data)
- Aborts if free disk space is below 2× the estimated raw-table growth

### `scraper:sync {type}`

Manual promotion of raw → production. Types: `players`, `rankings`, `matches`, `live_center`, `all`.

```bash
php artisan scraper:sync all              # promote everything
php artisan scraper:sync rankings --dry-run
php artisan scraper:sync matches --run=42  # only sync from ScraperRun #42
```

### `scraper:export`

Dump all scraped data to JSON archive in `storage/app/scraper-exports/`. Useful before destructive operations.

### `scraper:check [--fix]`

Validates the **environment** — PHP version, Node/npm/Chrome paths, Browsershot, DB connectivity, queue config, scraper tables. Run after deploys or environment changes.

### `scraper:health [--json] [--stuck-threshold=120] [--failure-window=7]`

Validates **operational state** — Python binaries + scripts, queue driver, scraper-queue worker, scheduler heartbeat, queued/failed job counts, Redis ping, stuck runs, last successful run per domain, recent failure rate, profixio.com reachability, disk space.

Exit code: 0 if healthy/degraded, non-zero if any check failed. Use `--json` for monitoring integrations.

### `scraper:cleanup [--older-than=30]`

Marks scraper runs stuck in `running` status for more than N minutes as `failed`. Recovers from hung processes.

### `scraper:cleanup-logs [--days=7] [--delete-archived=30]`

Archives filesystem scraper logs older than `--days` days (gzips them), deletes archives older than `--delete-archived` days.

---

## Scheduling

Defined in [routes/console.php](routes/console.php). Two scheduling mechanisms:

### 1. `scraper:start` — monthly full scrape

```
First Tuesday of each month at 02:00 → scraper:start <previous-month>
Cron: 0 2 1-7 * 2
```

Profixio finalizes monthly data on the first Monday; we scrape on the first Tuesday to give a 24h buffer.

### 2. Per-domain jobs — DB-settings driven

Configured via the `scraper_settings` table (rows like `schedule_players_enabled`, `schedule_players_frequency`, etc.). Defaults:

| Job | Default freq | Default day | Default time |
|---|---|---|---|
| `ScrapePlayersJob` | monthly | 1st | 03:00 |
| `ScrapeRankingsJob` (male) | weekly | sunday | 02:00 |
| `ScrapeRankingsJob` (female) | weekly | sunday | 02:30 (male +30m) |
| `ScrapeTransitionsJob` | weekly | monday | 03:30 (default off) |
| `ScrapeSeriesJob` | weekly | monday | 04:00 (default off) |
| `ScrapeLiveCenterJob` | daily | — | 05:00 (default off) |

All jobs use `->withoutOverlapping()->onOneServer()`.

### Other scheduled tasks

- Daily 00:00 — `scraper:cleanup-logs`
- Daily 01:00 — `backup:run --only-db`
- Monthly — `scraper:export`

---

## Configuration

### Environment variables (`.env`)

```env
# Scraper main URL
SCRAPER_MAIN_URL=https://www.profixio.com/fx/sbtf/

# Browser
SCRAPER_HEADLESS=true
SCRAPER_NODE_BINARY=/usr/local/bin/node
SCRAPER_NPM_BINARY=/usr/local/bin/npm
SCRAPER_CHROME_PATH=/usr/bin/chromium

# Python
SCRAPER_PYTHON_BINARY=python3
SCRAPER_PYTHON_TIMEOUT=21600   # 6h

# Per-domain schedule master switches
SCRAPER_SCHEDULE_RANKINGS=true
SCRAPER_SCHEDULE_PLAYERS=true
SCRAPER_SCHEDULE_TRANSITIONS=false
SCRAPER_SCHEDULE_SERIES=false
SCRAPER_SCHEDULE_LIVECENTER=false
SCRAPER_SCHEDULE_EXPORT=true

# Queue
SCRAPER_QUEUE_CONNECTION=redis   # 'redis' on prod, 'database' fallback
SCRAPER_QUEUE_NAME=scraper
```

See [config/scraper.php](config/scraper.php) for the full config tree (rate limits, retry backoff, parallel limits, batch sizes).

### Database settings (`scraper_settings` table)

Per-schedule overrides keyed by string keys like `schedule_rankings_frequency`, `schedule_rankings_day`, `schedule_rankings_time`, `schedule_rankings_enabled`. The `ScraperSetting::get('key', $default)` API reads these with 1h caching.

---

## Operations

### Running the scheduler

```bash
* * * * * cd /path/to/iracket && php artisan schedule:run >> /dev/null 2>&1
```

`php artisan schedule:list` shows the next scheduled occurrence of each task.

### Running queue workers

In production with Redis:

```bash
php artisan queue:work redis --queue=scraper --tries=3 --timeout=10800
```

Run under Supervisor — see [docs/PRODUCTION_QUEUE_SETUP.md](docs/PRODUCTION_QUEUE_SETUP.md) for the full Supervisor config.

### Monitoring a run in progress

```bash
# Tail logs in real time
php artisan pail --filter=scraper

# Or watch the DB
SELECT id, type, status, current_step, items_scraped, items_failed, started_at
FROM scraper_runs
WHERE status = 'running'
ORDER BY started_at DESC;
```

### Recovering a stuck run

```bash
php artisan scraper:cleanup --older-than=120   # marks runs >2h old as failed
```

### Manual one-off scrape

```bash
# Run rankings for May 2026, male, synchronously
php artisan scraper:run rankings --year=2026 --month=05 --gender=m

# Same but queued
php artisan scraper:run rankings --year=2026 --month=05 --gender=m --queue
```

---

## Production setup

For full installation steps see:
- [docs/PRODUCTION_SETUP.md](docs/PRODUCTION_SETUP.md) — server + DirectAdmin + Node binary paths
- [docs/PRODUCTION_QUEUE_SETUP.md](docs/PRODUCTION_QUEUE_SETUP.md) — Supervisor, queue workers, cron
- [docs/CHROMIUM_INSTALLATION.md](docs/CHROMIUM_INSTALLATION.md) — installing Chromium for Browsershot
- [docs/QUEUE_BASED_SCRAPING.md](docs/QUEUE_BASED_SCRAPING.md) — operational quick reference

Quick sanity check after install:

```bash
php artisan scraper:check
```

Should report all green for PHP version, extensions, Node/npm/Chrome paths, Browsershot, DB, queue, and scraper tables.

---

## Known gaps and gotchas

### Not yet implemented

(All planned items have shipped as of 2026-05-24.)

### Data integrity caveats

- **Player name matching is brittle**: sync services match `users` by `first_name|last_name` lowercased. Two "John Smith" players will silently merge into one record. No email-based dedup in the player import path (unlike user signups). Watch for this at backfill scale.
- **Manual matches can be overwritten silently**: when `MatchSyncService` finds a manual match (user-entered) that overlaps a scraped one, it replaces it. Orphan manual matches get marked `official=false` at end of sync.
- **Live center silently skips**: doubles (D1, D2) and any game where either player isn't already in `users`. Both get `is_synced=true` to prevent retry. Re-running with new players in DB will not retroactively sync the skipped games.

### Performance constraints

- **`RankingsScraper` timeout = 0 (unlimited)** — a hung Python process blocks its queue worker indefinitely. `scraper:cleanup` catches >30min as a heuristic, but valid long runs can exceed that. Real fix: per-atom hard timeout.
- **Rate limits** are fixed delays in config, not adaptive: 300ms between requests, 500ms between pages, 300ms after click. No backoff on 429 responses.
- **No login credentials needed** — all scraping uses an unauthenticated guest session (`login_public=SBTF.SE.BT`). No API key to rotate, but also no SLA from profixio. Aggressive backfill could trigger an IP block — recommend running from the known production IP, not a dev machine.

### Storage

- Scraped tables accumulate; old runs are NOT auto-pruned. Each monthly full scrape adds ~5000+ rankings rows × ~3MB. Plan for ~50MB/month growth on `scraped_*` tables.
- Backups land in `storage/backups/` (Spatie Backup config); rotation handled separately.
- Filesystem logs land in `storage/scraper_logs/` and get gzipped after 7 days by `scraper:cleanup-logs`.

---

## Glossary

| Term | Meaning |
|---|---|
| **Atom** | Smallest unit of one scrape operation (e.g. one month-gender for rankings, one date for live center) |
| **Domain** | A category of scraped data: rankings, players, matches, transitions, standings, live center |
| **Period** | A date string in profixio's format (YYYY.MM.DD) representing a roster snapshot |
| **Popup scraper** | The rankings scraper that clicks each player's name to extract match history from a popup window |
| **Full scrape** | A `scraper:start` execution covering all 11 steps for one month |
| **Sync** | Promotion of `scraped_*` rows to production tables; gated by `is_synced` flag |
| **Profixio** | profixio.com — the Swedish Table Tennis Federation's tournament platform; our upstream data source |
| **SBTF** | Svenska Bordtennisförbundet — the Swedish Table Tennis Federation |

---

## Pointers to related docs

- [docs/PRODUCTION_QUEUE_SETUP.md](docs/PRODUCTION_QUEUE_SETUP.md) — Supervisor + queue workers
- [docs/PRODUCTION_SETUP.md](docs/PRODUCTION_SETUP.md) — Server install
- [docs/CHROMIUM_INSTALLATION.md](docs/CHROMIUM_INSTALLATION.md) — Headless Chrome setup
- [docs/QUEUE_BASED_SCRAPING.md](docs/QUEUE_BASED_SCRAPING.md) — Operations quick reference
- [docs/calculation.md](docs/calculation.md) — How ranking points are calculated (Bubbler logic)
- [docs/scraper-analysis.md](docs/scraper-analysis.md) — Historical analysis of the predecessor .NET scraper
- [docs/old/](docs/old/) — Archived/outdated docs preserved for history
