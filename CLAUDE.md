# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

iRacket is a Laravel 12 web application for managing racquetball/badminton tournaments, rankings, and matches. It includes a web scraper system that pulls data from profixio.com.

**Stack**: Laravel 12, Livewire/Flux, Tailwind CSS 4, Pest PHP, SQLite

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

**UUID Routing**: User and GameMatch models use UUID instead of ID for route binding.

**Livewire Components**: No traditional controllers - all UI logic in `app/Livewire/`. Components handle validation, pagination, and real-time filtering.

**Scraper Data Flow**:
1. Queue jobs in `app/Jobs/Scraper/` dispatch scraper services
2. Services in `app/Services/Scraper/` extend `BaseScraperService`
3. Raw data stored in `scraped_*` tables with `is_synced` flag
4. `SyncService` validates and moves data to production tables

**Dual Logging**: Scraper logs to both filesystem (`storage/scraper_logs/`) and database (`ScrapeLog` model).

### Database Structure

**Core tables**: users, clubs, matches, monthly_rankings, club_monthly_rankings

**Scraper tables** (7 total): scraper_runs, scraper_logs, scraped_players, scraped_rankings, scraped_matches, scraped_transitions, scraped_standings

All scraped tables have `is_synced` flag and `synced_*_id` foreign keys for tracking imports.

### Key Relationships
- User belongs to Club, has many matches (as player1/player2)
- Matches link to users via player1/player2/winner/createdBy
- User and Club have monthly rankings

### Authentication
- Laravel Fortify + Socialite (Google, Apple)
- Spatie Permission for roles (Admin, Manager, User)
- Routes protected by `auth`, `verified`, `role:Admin|Manager` middleware

## Configuration

**Scraper config**: All settings in `config/scraper.php` (selectors, timeouts, schedules, retry logic)

**Environment variables for scraper**:
```env
SCRAPER_MAIN_URL=https://www.profixio.com/fx/sbtf/
SCRAPER_HEADLESS=true
SCRAPER_SCHEDULE_RANKINGS=true
SCRAPER_SCHEDULE_PLAYERS=true
```

Scraper uses Spatie Browsershot (headless Chrome via Puppeteer).
