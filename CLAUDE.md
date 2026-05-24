# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Project Overview

iRacket is a Laravel 12 web application for managing racquetball/badminton tournaments, rankings, and matches. It includes a web scraper system that pulls data from profixio.com.

**Stack**: Laravel 12, Livewire/Flux v2, Livewire Volt v1, Tailwind CSS 4, Alpine.js (via Flux), Pest PHP 4, MariaDB (Docker)

## Active work

As of 2026-05-25, the remaining open workstreams are:

1. **Mobile app deployment** — Flutter app in [flutter-app/](flutter-app/) is feature-complete and Codemagic pipelines are defined in [codemagic.yaml](codemagic.yaml). Outstanding manual steps tracked in the signing + deployment checklists in [APP.md § Deployment via Codemagic](APP.md#deployment-via-codemagic): upload Android keystore to Codemagic, register iOS App ID + create App Store Connect listing + API key, create Play Console app + service account, then add `android_signing:` / `app_store_connect:` / `google_play:` publishing blocks to the yaml.
2. **Scraper finalization** — see [SCRAPER.md](SCRAPER.md) and [SCRAPER_SMOKE_TESTS.md](docs/SCRAPER_SMOKE_TESTS.md) for current state and remaining work on the profixio.com ingest pipeline.

Everything else (web app, auth, rankings, admin, theming) is in maintenance.

## Commands

### Development
```bash
composer dev          # Full dev stack (server:8000, queue, pail, vite:5173)
composer setup        # Install deps & run migrations
npm run dev           # Vite dev server
npm run build         # Build frontend
```

### Testing
```bash
composer test                          # Run all tests (clears config, runs Pest)
php artisan test tests/Unit/Scraper    # Run specific directory
php artisan test --filter=testName     # Run single test
```

### Queue & Database
```bash
php artisan queue:listen --tries=1     # Process queue jobs
php artisan migrate:fresh              # Reset database
php artisan tinker                     # Interactive shell
```

### Scraper Commands
```bash
php artisan scraper:cleanup-logs --days=7 --delete-archived=30
```

## Architecture

### Key Patterns

**UUID Routing**: User and GameMatch models use `uuid` column, route key = `uuid`. Club uses `slug`.

**No Controllers**: All UI logic lives in `app/Livewire/`. No traditional controllers for web routes.

**Livewire Components**:
- Namespace: `App\Livewire\{Area}\{Resource}\{Action}` (Areas: Public, User, Admin, Settings, Auth, Components)
- Views: `resources/views/livewire/{area}/{resource}/{action}.blade.php`
- Layout declared in `render()` method: `->layout('components.layouts.xxx')`
- Public properties = reactive bindings, `$queryString` for URL-persistent filters
- Validation via `$rules` array or inline `$this->validate([...])`

**Scraper Data Flow**:
1. Queue jobs in `app/Jobs/Scraper/` dispatch scraper services
2. Services in `app/Services/Scraper/` extend `BaseScraperService`
3. Raw data stored in `scraped_*` tables with `is_synced` flag
4. `SyncService` validates and moves data to production tables

**Dual Logging**: Scraper logs to both filesystem (`storage/scraper_logs/`) and database (`ScrapeLog` model).

### Database Structure

**DB**: MariaDB running in Docker (NOT SQLite). `php artisan migrate` requires DB access.

**Core tables**: users, clubs, matches, monthly_rankings, club_monthly_rankings

**Scraper tables** (7): scraper_runs, scraper_logs, scraped_players, scraped_rankings, scraped_matches, scraped_transitions, scraped_standings

**Live Center tables**: live_match_details, live_match_games, live_match_sets, live_match_points

All scraped tables have `is_synced` flag and `synced_*_id` foreign keys.

### Key Models
- `User` — UUID routing (`getRouteKeyName` = `uuid`), `HasRoles`, `TwoFactorAuthenticatable`
- `GameMatch` — UUID routing
- `Club` — slug routing
- `MonthlyRanking`, `ClubMonthlyRanking`, `Term`, `Contact`, `Banner`, `Notification`, `ClubTransition`

### Key Relationships
- User belongs to Club, has many matches (as player1/player2/winner)
- User and Club have monthly rankings

### Authentication
- Laravel Fortify + Socialite (Google, Apple)
- Spatie Permission for roles (Admin, Manager, User)
- Routes protected by `auth`, `verified`, `connected`, `role:Admin|Manager` middleware

## Layouts

| Layout | Used for | File |
|---|---|---|
| `components.layouts.public.landing` | Public home page | AOS animations, fixed header, lang switcher, dark toggle |
| `components.layouts.app` | Authenticated users (→ app/mobile) | Fixed top bar h-14, bottom nav h-16, `pt-14 pb-20` |
| `components.layouts.admin` | Admin panel | Sidebar w-64, slide-in mobile, sticky header |
| `components.layouts.auth` | Auth pages (→ auth/simple) | Simple centered layout |

## Design System

**Font**: Instrument Sans (Bunny fonts CDN)

**Accent color**: `#34C759` (green, same in light/dark) — use `text-accent`, `bg-accent`, `ring-accent`

**Dark mode**: Custom CSS variant `(&:where(.dark, .dark *))`. Class toggled via JS on `<html>`. Default = dark. Managed via `localStorage.theme`.

**Color tokens (Tailwind)**:
- Background: `bg-white` / `dark:bg-zinc-900` (page), `bg-zinc-100` / `dark:bg-zinc-800` (inputs/cards)
- Borders: `border-zinc-200` / `dark:border-zinc-700`
- Text primary: `text-zinc-900` / `dark:text-white`
- Text muted: `text-zinc-500` or `text-zinc-600` / `dark:text-zinc-400`
- Nav active: `bg-accent text-white`
- Nav inactive: `text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700`

**Focus rings**: `focus:ring-2 focus:ring-accent focus:border-transparent focus:outline-none`

**Inputs**: `px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent`

**Rounded**: `rounded-lg` for panels/buttons, `rounded-xl` for inputs/cards

## Routing Conventions

```php
// Public
Route::get('/', PublicHome::class)->name('home');

// Authenticated
Route::middleware(['auth'])->group(...)

// Verified + connected users
->middleware(['verified', 'connected'])

// Admin (Admin OR Manager)
Route::middleware(['auth', 'role:Admin|Manager'])->prefix('admin')->name('admin.')->group(...)

// Admin-only inside admin group
Route::middleware(['role:Admin'])->group(...)
```

**Route model binding**: `{user}` (UUID), `{match}` (UUID), `{club:slug}`, `{id}` (admin routes use integer ID)

**Navigation & `wire:navigate`**:
- Use `wire:navigate` on internal `<a>` tags **only when source and destination share the same layout**.
- Do **NOT** use `wire:navigate` when navigating across layout boundaries (e.g., landing → app, app → admin, auth → app).
- Cross-auth-boundary links (login/logout, landing → dashboard) must never use `wire:navigate`.

## Localization

- Languages: `en`, `sv` (Swedish)
- Switch via `GET /locale/{locale}` → `locale.switch` route
- All UI text via `__('key')` or `__('file.key')`

## Rules — Always / Never

**Always**:
- Use `wire:navigate` on internal links **within the same layout** (same layout = safe, different layout = no wire:navigate)
- Specify layout in `render()` via `->layout('...')`
- Use existing Tailwind tokens (zinc scale, accent) — no custom colors unless matching existing palette
- Use `$rules` or inline `validate()` in Livewire for all form validation
- Follow namespace/view path conventions for new Livewire components

**Never**:
- Never use traditional controllers for web UI routes
- Never create new CSS classes when Tailwind utilities suffice
- Never hardcode colors that have a CSS token (`var(--color-accent)` / `text-accent`)
- Never run `php artisan migrate` without DB access (MariaDB in Docker)

## Configuration

**Scraper config**: `config/scraper.php`

**Environment variables for scraper**:
```env
SCRAPER_MAIN_URL=https://www.profixio.com/fx/sbtf/
SCRAPER_HEADLESS=true
SCRAPER_SCHEDULE_RANKINGS=true
SCRAPER_SCHEDULE_PLAYERS=true
```

Scraper uses Spatie Browsershot (headless Chrome via Puppeteer) + Python Playwright scripts in `scripts/scraper/`.
