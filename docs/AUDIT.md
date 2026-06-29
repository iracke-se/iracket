# Project Audit (2026-06-29)

## Unused Packages — Remove

These packages are installed but have zero references in the codebase:

| Package | Type | Reason |
|---|---|---|
| `livewire/volt` | composer | No `@volt` directives anywhere — project uses standard Livewire components |
| `laravel/sail` | composer | Project uses DDEV, not Sail (see `.ddev/` directory) |
| `fakerphp/faker` | composer dev | Installed but unused in any seeders or tests |
| `axios` | npm | HTTP client never imported or called — app uses Livewire form submissions |
| `@puppeteer/browsers` | npm | Redundant — already a transitive dependency of `puppeteer` |
| `autoprefixer` | npm | Not needed — Tailwind CSS v4 handles vendor prefixing internally |

---

## Unused Scraper Code — Remove

Verified by reading every Livewire component and blade view. Everything below either produces data that is never displayed in the app, or is dead code that is never loaded.

### Series Standings pipeline

The entire pipeline runs and syncs into `club_standings`, but no Livewire component or view ever queries that table.

| Component | Type |
|---|---|
| `app/Services/Scraper/SeriesScraper.php` | PHP service |
| `app/Services/Scraper/StandingsSyncService.php` | PHP service |
| `app/Jobs/Scraper/ScrapeSeriesJob.php` | PHP job |
| `club_standings` | DB table |

### Dead files

| File | Reason |
|---|---|
| `app/Services/Scraper/RankingsScraper.php.backup` | Not autoloaded |
| `app/Services/Scraper/_deprecated/LiveCenterScraper.php.bak` | Superseded by `LiveCenterDetailsScraper` |
| `app/Services/Scraper/_deprecated/MatchSyncService.php.bak` | Superseded by `MatchSyncService` |
| `app/Console/Commands/ScraperYearCommand.php` | Duplicates `scraper:backfill --from --to` |
| `.env.bak` | Backup of environment config — check contents before deleting |

---

## Cleanup Commands

```bash
# Unused composer packages
composer remove livewire/volt laravel/sail
composer remove --dev fakerphp/faker

# Unused npm packages
npm remove @puppeteer/browsers autoprefixer

# Series standings pipeline
rm app/Services/Scraper/SeriesScraper.php
rm app/Services/Scraper/StandingsSyncService.php
rm app/Jobs/Scraper/ScrapeSeriesJob.php

# Dead files
rm app/Services/Scraper/RankingsScraper.php.backup
rm -r app/Services/Scraper/_deprecated/
rm app/Console/Commands/ScraperYearCommand.php
rm .env.bak

# Drop DB table with no app consumers
php artisan tinker --execute="Schema::drop('club_standings');"
```

