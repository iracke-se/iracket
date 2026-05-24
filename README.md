# iRacket

iRacket is a Laravel web application for managing racquetball/badminton tournaments, rankings, and matches. It includes a scraper system that pulls data from [profixio.com](https://www.profixio.com/) and stores it for syncing into the application's production tables.

## Stack

- **Backend**: Laravel 12, PHP 8.2+
- **UI**: Livewire 3 with Flux v2, Livewire Volt v1, Alpine.js
- **Styling**: Tailwind CSS 4
- **Database**: MariaDB (Docker)
- **Auth**: Laravel Fortify + Socialite (Google, Apple), Spatie Permission for roles
- **Testing**: Pest PHP 4
- **Scraping**: Spatie Browsershot (headless Chrome via Puppeteer) + Python Playwright scripts

## Requirements

- PHP 8.2+
- Composer 2+
- Node.js 18+
- MariaDB (typically run via Docker)
- Python 3.11+ (for the Playwright scraper scripts in `scripts/scraper/`)

## Getting Started

```bash
# Install dependencies and run migrations
composer setup

# Start the full dev stack (server, queue worker, logs, vite)
composer dev
```

`composer dev` runs four processes concurrently:

| Process | Port / Purpose |
|---|---|
| `php artisan serve` | http://localhost:8000 |
| `php artisan queue:listen --tries=1` | Queue worker |
| `php artisan pail --timeout=0` | Live log tailer |
| `npm run dev` | Vite dev server on :5173 |

## Common Commands

### Frontend
```bash
npm run dev      # Vite dev server
npm run build    # Production build
```

### Testing
```bash
composer test                          # Run the full Pest suite
php artisan test tests/Unit/Scraper    # Run a specific directory
php artisan test --filter=testName     # Run a single test
```

### Queue & Database
```bash
php artisan queue:listen --tries=1     # Process queue jobs
php artisan migrate:fresh              # Reset the database
php artisan tinker                     # Interactive shell
```

### Scraper
```bash
php artisan scraper:cleanup-logs --days=7 --delete-archived=30
```

## Project Layout

```
app/
  Livewire/        UI components, grouped by area (Public, User, Admin, Settings, Auth, Components)
  Services/Scraper/  Scraper services (extend BaseScraperService)
  Jobs/Scraper/    Queue jobs that dispatch the scraper services
  Models/          Eloquent models (User, GameMatch, Club, MonthlyRanking, ...)
docs/              Architecture and operations documentation
resources/
  backup/          SQL backups and seed data
  views/           Blade templates and Livewire view files
routes/            Route definitions (web, auth, admin)
scripts/scraper/   Python Playwright scrapers
storage/scraper_logs/  Filesystem log output for scraper runs
```

## Architecture Notes

- **No controllers for web UI** — all UI logic lives in `app/Livewire/`.
- **UUID routing** for `User` and `GameMatch`; `Club` uses a `slug` column.
- **Scraper data flow**: queue job → service (extends `BaseScraperService`) → `scraped_*` tables with `is_synced` flag → `SyncService` promotes validated rows into production tables.
- **Dual logging** — scraper runs log to both `storage/scraper_logs/` and the `ScrapeLog` model.
- **Roles**: `Admin`, `Manager`, `User`. Admin routes use `auth + role:Admin|Manager`; admin-only sections add an inner `role:Admin` middleware.

See [docs/](docs/) for deeper architecture and operations docs (production setup, queue setup, scraper analysis, calculation logic, etc.).

## Configuration

Scraper configuration lives in `config/scraper.php`. Key environment variables:

```env
SCRAPER_MAIN_URL=https://www.profixio.com/fx/sbtf/
SCRAPER_HEADLESS=true
SCRAPER_SCHEDULE_RANKINGS=true
SCRAPER_SCHEDULE_PLAYERS=true
```

## Localization

The app ships with English (`en`) and Swedish (`sv`). Locale is switched via `GET /locale/{locale}` (named route `locale.switch`). All UI text uses `__('key')`.

## License

Proprietary — all rights reserved.
