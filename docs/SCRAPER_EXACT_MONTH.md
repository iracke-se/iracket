# Scrape Exact Month

Scrapes only the data for a specific month. Replace `2026` / `01` with the month you want.

Only **Rankings & Matches** and **Live Center** use the exact month, so those are the only scrapers here.

## Commands

### Per month

```bash
# 1. Rankings & Matches (male + female)
ddev artisan scraper:run rankings --year=2026 --month=01 --gender=m
ddev artisan scraper:run rankings --year=2026 --month=01 --gender=k

# 2. Sync so the matches land in the `matches` table (Live Center needs this)
ddev artisan scraper:sync all

# 3. Live Center — driven off the matches just synced (no month-shift)
ddev artisan scraper:run live_center --from-matches --year=2026

# 4. Sync again to push Live Center games into matches
ddev artisan scraper:sync all
```

> **Do NOT use** `scraper:run live_center --month=2026-01`. The `--month` form shifts back
> one month (the play month) and fails with "No dates found" when that month has no Live
> Center data. Always sync first, then use `--from-matches`, which reads the real play dates
> from your matches table.

### Once at the end (not per month)

```bash
# Club transitions = full transfer history, not month-specific. Run once.
ddev artisan scraper:run transitions
ddev artisan scraper:sync all
```

Run them in this order. Always use `ddev artisan` (not `php artisan`).

## Scrape a whole year automatically (`scraper:year`)

Instead of running each month by hand, the standalone `scraper:year` command does the
whole year for you, month by month, in order. It only calls the commands above — it does
not change any existing scraper code.

```bash
ddev artisan scraper:year 2026
```

What it does, in order:

1. For each month (`01`..`12`): rankings male → rankings female → `scraper:sync all` (run **twice**).
2. After all months: Live Center for the whole year via
   `scraper:run live_center --from-matches --year=YYYY` → `scraper:sync all` (run **twice**).

> ⚠ **This is a long job** — roughly ~1.5h per month (~20h for a full year). The per-month
> sync means progress is saved as it goes, so a crash never loses finished months.

**Why Live Center uses `--from-matches`:** the per-month syncs put all the year's matches
into the `matches` table first, so `--from-matches` reads those real play dates and scrapes
Live Center for exactly those days. This avoids the month-shift that makes
`live_center --month=YYYY-MM` fail with "No dates found" when a month has no Live Center
data. A date with no coverage is skipped (non-fatal) instead of aborting the run.

### Options

| Option | What it does |
|---|---|
| `--resume` | Skip months that already finished (completed rankings runs for both genders). Use this to restart after a crash without redoing months. |
| `--parallel` | Scrape male + female at the same time (~halves the time). |
| `--from=3 --to=9` | Only scrape part of the year (months 3–9). |
| `--skip-live-center` | Rankings + matches only, no Live Center. |

Examples:

```bash
# Full year, resume-safe restart after an interruption
ddev artisan scraper:year 2026 --resume

# Faster (both genders at once)
ddev artisan scraper:year 2026 --parallel

# Only March–June
ddev artisan scraper:year 2026 --from=3 --to=6
```

Notes:
- For the **current** year it automatically stops at the current month (no future months).
- **Club Transitions** and **Series Standings** are not included — run transitions separately
  once when you're done (see "Once at the end" above).

## Do I lose anything by not running the full scraper?

No. Running these commands one by one gives the same result as the full `scraper:start`
pipeline. `scraper:start` is just convenience (orchestration + backup + progress UI), not
extra data.

What you skip versus the full pipeline:

- **Series Standings** — not displayed anywhere in the app, so zero real loss.
- **Players** — only enriches existing users, creates no new ones, so effectively no loss.
  (Rankings sync already creates users with their club, gender, birth year and SBTF id.)

So the only thing with real value is **Club Transitions**, and that's the one extra command
above — run it once whenever you're done.

## Two things to remember (vs. `scraper:start`)

1. **No automatic DB backup.** `scraper:start` makes one before it runs; running commands
   manually you don't get that safety net.
2. **Order matters.** Run rankings before Live Center, and always run `scraper:sync all`
   after scraping — the scrape commands only fill the staging (`scraped_*`) tables; nothing
   reaches the production tables until you sync.
