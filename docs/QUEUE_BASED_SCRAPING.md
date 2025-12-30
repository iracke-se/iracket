# Queue-Based Scraping - Quick Reference

## What Was Created

### 1. New Commands

#### `scraper:check` - Environment Diagnostic Tool
Validates that all required tools and configurations are in place.

```bash
# Quick check
php artisan scraper:check

# Detailed check with verbose output
php artisan scraper:check --verbose
```

**Checks performed:**
- ✓ PHP version (requires 8.2+)
- ✓ PHP extensions (pdo_sqlite, sqlite3, mbstring, xml, curl, zip, pcntl)
- ✓ Node.js installation and path
- ✓ npm installation and path
- ✓ Chrome/Chromium installation and path
- ✓ Browsershot and Puppeteer packages
- ✓ Database connection
- ✓ Storage directory permissions
- ✓ Environment variables (.env configuration)
- ✓ Queue configuration
- ✓ Queue worker status
- ✓ Scraper database tables
- ✓ Test connection to Google (validates full scraping pipeline)

#### `scraper:queue` - Dispatch Scrapers to Queue
Sends scraper jobs to the queue instead of running them directly.

```bash
# Dispatch full scrape to queue
php artisan scraper:queue start 2024-12

# Dispatch smart-scrape (rankings + series only)
php artisan scraper:queue smart-scrape 2024-12

# Dispatch individual scrapers
php artisan scraper:queue rankings 2024-12 --gender=male
php artisan scraper:queue players 2024-12
php artisan scraper:queue series 2024-12

# With options
php artisan scraper:queue start 2024-12 --no-backup --skip-sync

# Test with limits
php artisan scraper:queue rankings 2024-12 \
  --gender=male \
  --limit-periods=2 \
  --limit-divisions=2
```

### 2. New Job Class

**`App\Jobs\Scraper\RunScraperJob`** - Queue job wrapper for scraper commands
- Handles timeouts (2-4 hours depending on scraper type)
- Logs start/completion/failures
- Automatic retry on failure (configurable)
- Can be dispatched directly in code:

```php
use App\Jobs\Scraper\RunScraperJob;

// Dispatch full scrape
RunScraperJob::dispatch('scraper:start', ['month' => '2024-12']);

// Dispatch with options
RunScraperJob::dispatch('scraper:start',
    ['month' => '2024-12'],
    ['--no-backup' => true]
);
```

### 3. Documentation

- **[PRODUCTION_QUEUE_SETUP.md](PRODUCTION_QUEUE_SETUP.md)** - Complete production setup guide with Supervisor
- **[PRODUCTION_SETUP.md](PRODUCTION_SETUP.md)** - DirectAdmin-specific setup instructions
- **[SMART_SCRAPE_PLAN.md](SMART_SCRAPE_PLAN.md)** - Plan for smart-scrape command (to be implemented)

---

## Production Workflow

### Initial Setup (One-Time)

```bash
# 1. Validate environment
php artisan scraper:check --verbose

# 2. Fix any reported issues
# Follow suggestions in check output

# 3. Install Supervisor
sudo apt-get install supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor

# 4. Configure queue worker
sudo nano /etc/supervisor/conf.d/iracket-queue.conf
```

Supervisor config template:
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
```

```bash
# 5. Start queue workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start iracket-queue:*

# 6. Verify workers are running
sudo supervisorctl status
```

### Daily Usage

```bash
# Dispatch scrape to queue
php artisan scraper:queue start 2024-12

# Monitor queue
php artisan queue:monitor

# Check logs
tail -f storage/logs/queue-worker.log
tail -f storage/logs/laravel.log
```

### Monitoring

```bash
# Check queue worker status
sudo supervisorctl status iracket-queue:*

# View worker logs
sudo supervisorctl tail -f iracket-queue:iracket-queue_00

# Check pending jobs
php artisan tinker
>>> DB::table('jobs')->count();

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Troubleshooting

```bash
# Re-validate environment
php artisan scraper:check --verbose

# Restart queue workers
sudo supervisorctl restart iracket-queue:*

# Clear queue
php artisan queue:flush

# Check for stuck processes
ps aux | grep "queue:work"
```

---

## Local Development

For local development, you can run queue workers without Supervisor:

```bash
# Terminal 1: Run queue worker
php artisan queue:work --verbose

# Terminal 2: Dispatch jobs
php artisan scraper:queue rankings 2024-12 --limit-periods=1

# Or run directly without queue
php artisan scraper:start 2024-12 --limit-periods=1
```

---

## Migration from Direct Execution

### Old Way (Direct Execution)
```bash
# Blocks terminal for 2-3 hours
php artisan scraper:start 2024-12

# Or with nohup
nohup php artisan scraper:start 2024-12 > scraper.log 2>&1 &
```

### New Way (Queue-Based) ✅
```bash
# Dispatches to queue, returns immediately
php artisan scraper:queue start 2024-12

# Queue worker handles execution
# Supervisor ensures worker stays running
# Automatic retries on failure
# Can monitor with: php artisan queue:monitor
```

---

## Configuration Tips

### Queue Connection

Set in `.env`:
```env
QUEUE_CONNECTION=database
```

For production with many jobs, consider Redis:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Job Timeouts

Edit `RunScraperJob.php` if needed:
```php
public int $timeout = 7200;  // 2 hours for individual scrapers
public int $timeout = 14400; // 4 hours for full scrape
```

### Worker Configuration

In Supervisor config:
```ini
numprocs=2          # Number of parallel workers (1-4 recommended)
timeout=14400       # Max execution time (4 hours)
stopwaitsecs=14400  # Time to wait for graceful shutdown
```

---

## Scheduled Scraping (Cron)

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily full scrape at 2 AM (dispatches to queue)
    $schedule->call(function () {
        RunScraperJob::dispatch('scraper:start', [
            'month' => now()->format('Y-m')
        ]);
    })->dailyAt('02:00')
      ->withoutOverlapping()
      ->onOneServer();

    // Weekly smart-scrape on Monday at 6 AM
    $schedule->call(function () {
        RunScraperJob::dispatch('scraper:smart-scrape', [
            'month' => now()->format('Y-m')
        ]);
    })->weeklyOn(1, '06:00')
      ->withoutOverlapping()
      ->onOneServer();
}
```

Then add Laravel scheduler to cron:
```bash
crontab -e
```

Add:
```cron
* * * * * cd /path/to/iracket && php artisan schedule:run >> /dev/null 2>&1
```

---

## Summary

| Feature | Direct Execution | Queue-Based (New) |
|---------|-----------------|-------------------|
| **Reliability** | Manual restart if fails | Auto-retry on failure |
| **Monitoring** | Check logs manually | `queue:monitor`, Supervisor |
| **Background** | Requires nohup/screen | Built-in background processing |
| **Process Management** | Manual | Supervisor auto-restart |
| **Resource Control** | Hard to limit | Worker limits configurable |
| **Production Ready** | ⚠️ Not recommended | ✅ Recommended |

**Recommendation**: Always use `scraper:queue` in production with Supervisor-managed workers.
