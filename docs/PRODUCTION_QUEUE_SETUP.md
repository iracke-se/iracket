# Production Setup - Queue-Based Scraper with Supervisor

## Overview

This guide sets up the scraper to run as **queue jobs** managed by **Supervisor** - the recommended production approach.

### Why Queue-Based?

✅ **Reliability**: Jobs automatically retry on failure
✅ **Scalability**: Multiple workers can process jobs in parallel
✅ **Monitoring**: Track job status, failures, and retries
✅ **Resource Management**: Supervisor ensures workers stay running
✅ **Background Processing**: No need to keep terminal sessions open

---

## Quick Start

```bash
# 1. Check if scraper environment is ready
php artisan scraper:check

# 2. Dispatch scraper to queue
php artisan scraper:queue start 2024-12

# 3. Monitor queue
php artisan queue:monitor
```

---

## Step-by-Step Setup

### 1. Prerequisites

Before starting, ensure you have:
- PHP 8.2+
- Node.js and npm
- Chrome/Chromium browser
- Supervisor (process manager)
- SQLite or MySQL database

### 2. Run Environment Check

```bash
# Check if all tools are accessible
php artisan scraper:check --verbose

# Fix any issues reported by the check command
```

Expected output:
```
╔════════════════════════════════════════════════════════╗
║          SCRAPER ENVIRONMENT CHECK                     ║
╚════════════════════════════════════════════════════════╝

  ✓ PHP Version: 8.2.x
  ✓ PHP Extensions: All required extensions loaded
  ✓ Node.js: v20.x.x at /usr/bin/node
  ✓ npm: 10.x.x at /usr/bin/npm
  ✓ Chrome: Google Chrome 120.x.x
  ✓ Browsershot: Package installed, Puppeteer 21.x.x
  ✓ Database: Connected (sqlite)
  ✓ Storage Permissions: All directories writable
  ✓ Environment Variables: All required variables set
  ✓ Queue: Configured with 'database' driver
  ✓ Database Tables: All scraper tables exist
  ✓ Scraper Test: Successfully fetched test page

╔════════════════════════════════════════════════════════╗
║                    SUMMARY                             ║
╚════════════════════════════════════════════════════════╝

  Passed:   12
  Failed:   0
  Warnings: 0

✓  All checks passed! Scraper is ready to run.
```

### 3. Configure Environment

Edit your `.env` file:

```env
# Queue Configuration
QUEUE_CONNECTION=database

# Scraper Paths (use actual paths from scraper:check)
SCRAPER_NODE_BINARY=/usr/bin/node
SCRAPER_NPM_BINARY=/usr/bin/npm
SCRAPER_CHROME_PATH=/usr/bin/chromium-browser

# Scraper Settings
SCRAPER_HEADLESS=true
SCRAPER_TIMEOUT=60000
```

Find correct paths:
```bash
which node      # Copy output to SCRAPER_NODE_BINARY
which npm       # Copy output to SCRAPER_NPM_BINARY
which chromium-browser || which chromium || which google-chrome  # Copy to SCRAPER_CHROME_PATH
```

### 4. Install Supervisor

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor

# Start Supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
sudo systemctl status supervisor
```

### 5. Configure Queue Worker with Supervisor

Create Supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/iracket-queue.conf
```

Add this configuration:

```ini
[program:iracket-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/iracket/artisan queue:work database --sleep=3 --tries=1 --max-time=0 --timeout=14400
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/iracket/storage/logs/queue-worker.log
stopwaitsecs=14400
startsecs=0
```

**Important settings:**
- `command`: Replace `/path/to/iracket` with your actual app path
- `user`: Change to your web server user (www-data, nginx, apache, etc.)
- `numprocs=2`: Run 2 queue workers in parallel (adjust based on your needs)
- `timeout=14400`: 4 hours (for long-running scrapes)
- `stopwaitsecs=14400`: Allow 4 hours for graceful shutdown

### 6. Start Queue Workers

```bash
# Update Supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start queue workers
sudo supervisorctl start iracket-queue:*

# Check status
sudo supervisorctl status

# Expected output:
# iracket-queue:iracket-queue_00   RUNNING   pid 12345, uptime 0:00:10
# iracket-queue:iracket-queue_01   RUNNING   pid 12346, uptime 0:00:10
```

### 7. Verify Queue is Working

```bash
# Dispatch a test job
php artisan scraper:queue rankings 2024-12 --limit-periods=1 --limit-divisions=1

# Monitor queue
php artisan queue:monitor

# Check worker logs
tail -f storage/logs/queue-worker.log

# Check Laravel logs
tail -f storage/logs/laravel.log
```

---

## Usage

### Dispatch Scrapers to Queue

```bash
# Full scrape (all 12 steps)
php artisan scraper:queue start 2024-12

# Smart scrape (rankings + series only)
php artisan scraper:queue smart-scrape 2024-12

# Individual scrapers
php artisan scraper:queue rankings 2024-12 --gender=male
php artisan scraper:queue players 2024-12
php artisan scraper:queue series 2024-12

# With options
php artisan scraper:queue start 2024-12 --no-backup
php artisan scraper:queue start 2024-12 --skip-sync --skip-bubbler

# Test with limits
php artisan scraper:queue rankings 2024-12 \
  --gender=male \
  --limit-periods=2 \
  --limit-divisions=2
```

### Monitor Queue

```bash
# Real-time monitoring
php artisan queue:monitor

# Check pending jobs
php artisan tinker
>>> DB::table('jobs')->count();

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Manage Queue Workers

```bash
# View status
sudo supervisorctl status iracket-queue:*

# Restart workers
sudo supervisorctl restart iracket-queue:*

# Stop workers
sudo supervisorctl stop iracket-queue:*

# Start workers
sudo supervisorctl start iracket-queue:*

# View worker logs
sudo supervisorctl tail -f iracket-queue:iracket-queue_00
```

---

## Scheduling (Cron)

Set up automatic scraping with Laravel scheduler:

### 1. Edit Kernel.php

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Daily full scrape at 2 AM
    $schedule->command('scraper:queue start ' . now()->format('Y-m'))
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->onOneServer();

    // Weekly smart scrape (rankings + series) at 6 AM
    $schedule->command('scraper:queue smart-scrape ' . now()->format('Y-m'))
        ->weeklyOn(1, '06:00') // Monday at 6 AM
        ->withoutOverlapping()
        ->onOneServer();
}
```

### 2. Add Cron Entry

```bash
# Edit crontab
crontab -e
```

Add Laravel scheduler:
```cron
* * * * * cd /path/to/iracket && php artisan schedule:run >> /dev/null 2>&1
```

Or via DirectAdmin:
1. DirectAdmin → **Advanced Features** → **Cron Jobs**
2. Command: `cd /path/to/iracket && php artisan schedule:run`
3. Schedule: `* * * * *` (every minute)

---

## Monitoring & Logging

### View Scraper Runs

```bash
# List recent runs
php artisan tinker
>>> App\Models\Scraper\ScraperRun::latest()->take(5)->get(['id', 'type', 'status', 'items_scraped']);

# Check running scrapers
>>> App\Models\Scraper\ScraperRun::where('status', 'running')->get();
```

### Check Logs

```bash
# Queue worker logs
tail -f storage/logs/queue-worker.log

# Laravel application logs
tail -f storage/logs/laravel.log

# Scraper-specific logs
ls -lh storage/scraper_logs/
tail -f storage/scraper_logs/scraper-run-*.log
```

### Monitor System Resources

```bash
# Check CPU/Memory usage
top -u www-data

# Check queue worker processes
ps aux | grep "queue:work"

# Check database size
du -h database/database.sqlite
```

---

## Troubleshooting

### Queue Workers Not Starting

```bash
# Check Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Check if Supervisor is running
sudo systemctl status supervisor

# Restart Supervisor
sudo systemctl restart supervisor
sudo supervisorctl reread
sudo supervisorctl update
```

### Jobs Failing Immediately

```bash
# Check failed jobs table
php artisan queue:failed

# View specific failure
php artisan queue:failed --id=1

# Check environment
php artisan scraper:check --verbose

# Test manually
php artisan scraper:run rankings --gender=male --limit-periods=1
```

### Chrome/Puppeteer Errors

```bash
# Test Chrome manually
/usr/bin/chromium-browser --headless --no-sandbox --dump-dom https://google.com

# Check missing dependencies
ldd /usr/bin/chromium-browser | grep "not found"

# Install missing libraries
sudo apt-get install -y libgbm1 libasound2 libatk-bridge2.0-0
```

### High Memory Usage

```bash
# Reduce number of workers in Supervisor config
sudo nano /etc/supervisor/conf.d/iracket-queue.conf
# Change: numprocs=1 (instead of 2)

# Restart workers
sudo supervisorctl restart iracket-queue:*

# Add memory limits
# In supervisor config, add: command=php -d memory_limit=512M artisan queue:work...
```

### Queue Getting Stuck

```bash
# Restart queue workers
sudo supervisorctl restart iracket-queue:*

# Clear stuck jobs
php artisan queue:flush

# Check for locked processes
ps aux | grep "queue:work"
# Kill if needed: sudo kill -9 <pid>
```

---

## Maintenance

### Regular Tasks

```bash
# Weekly: Clean up old logs
php artisan scraper:cleanup --days=7 --delete-archived=30

# Monthly: Vacuum SQLite database (if using SQLite)
sqlite3 database/database.sqlite "VACUUM;"

# Monthly: Clear failed jobs
php artisan queue:flush
```

### Backup Before Scraping

```bash
# Manual backup
cp database/database.sqlite database/backups/database-$(date +%Y%m%d).sqlite

# The scraper:start command creates automatic backups unless --no-backup is used
```

### Updating Code

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
sudo supervisorctl restart iracket-queue:*
```

---

## DirectAdmin-Specific Setup

### 1. Set PHP Version
- DirectAdmin → **PHP Version Selector**
- Select PHP 8.2+
- Enable extensions: sqlite3, pdo_sqlite, mbstring, xml, curl, zip, pcntl

### 2. Install Supervisor
```bash
# SSH into server
ssh user@your-server.com

# Install Supervisor
sudo yum install supervisor  # CentOS
# or
sudo apt-get install supervisor  # Ubuntu

# Enable and start
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 3. Set Up Cron
- DirectAdmin → **Advanced Features** → **Cron Jobs**
- Command: `cd /home/username/domains/yourdomain.com/public_html && php artisan schedule:run`
- Schedule: `* * * * *`

### 4. File Permissions
```bash
sudo chown -R username:username /path/to/iracket
sudo chmod -R 775 storage bootstrap/cache
```

---

## Best Practices

1. **Always run `scraper:check` before deploying** to production
2. **Use queue for all scrapes** - never run long scrapes directly
3. **Monitor queue workers** with Supervisor
4. **Set up log rotation** to prevent disk space issues
5. **Enable automatic backups** (built into scraper:start)
6. **Use --limit flags** when testing in production
7. **Schedule scrapes during off-peak hours** (2-6 AM)
8. **Keep Supervisor config updated** when changing timeouts

---

## Quick Reference

```bash
# Environment Check
php artisan scraper:check

# Dispatch to Queue
php artisan scraper:queue start 2024-12

# Monitor
php artisan queue:monitor
sudo supervisorctl status

# Logs
tail -f storage/logs/queue-worker.log
tail -f storage/logs/laravel.log

# Manage Workers
sudo supervisorctl restart iracket-queue:*
sudo supervisorctl status iracket-queue:*

# Failed Jobs
php artisan queue:failed
php artisan queue:retry all
```
