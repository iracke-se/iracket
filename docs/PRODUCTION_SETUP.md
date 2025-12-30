# Production Setup Guide - DirectAdmin

## Prerequisites

### 1. Install Node.js and npm
```bash
# SSH into your DirectAdmin server
ssh user@your-server.com

# Check if Node.js is installed
node --version
npm --version

# If not installed, install Node.js (via nvm recommended)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install --lts
nvm use --lts
```

### 2. Install Chrome/Chromium
```bash
# For Debian/Ubuntu-based systems
sudo apt-get update
sudo apt-get install -y chromium-browser chromium-chromedriver

# For CentOS/RHEL-based systems
sudo yum install -y chromium chromium-headless

# Verify installation
which chromium-browser
# or
which chromium
```

### 3. Install Puppeteer Dependencies
```bash
# Install required system libraries for headless Chrome
sudo apt-get install -y \
  ca-certificates \
  fonts-liberation \
  libappindicator3-1 \
  libasound2 \
  libatk-bridge2.0-0 \
  libatk1.0-0 \
  libc6 \
  libcairo2 \
  libcups2 \
  libdbus-1-3 \
  libexpat1 \
  libfontconfig1 \
  libgbm1 \
  libgcc1 \
  libglib2.0-0 \
  libgtk-3-0 \
  libnspr4 \
  libnss3 \
  libpango-1.0-0 \
  libpangocairo-1.0-0 \
  libstdc++6 \
  libx11-6 \
  libx11-xcb1 \
  libxcb1 \
  libxcomposite1 \
  libxcursor1 \
  libxdamage1 \
  libxext6 \
  libxfixes3 \
  libxi6 \
  libxrandr2 \
  libxrender1 \
  libxss1 \
  libxtst6 \
  lsb-release \
  wget \
  xdg-utils
```

## 4. Configure Laravel Environment

### Update .env file in production
```bash
cd /path/to/your/laravel/app

# Edit .env file
nano .env
```

Add/update these variables:
```env
# Find correct paths on your system
SCRAPER_NODE_BINARY=/usr/bin/node  # or $(which node)
SCRAPER_NPM_BINARY=/usr/bin/npm    # or $(which npm)
SCRAPER_CHROME_PATH=/usr/bin/chromium-browser  # or /usr/bin/chromium

# Scraper settings
SCRAPER_HEADLESS=true
SCRAPER_TIMEOUT=60000
SCRAPER_MAIN_URL=https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php

# Queue settings
QUEUE_CONNECTION=database
```

### Find correct binary paths
```bash
# Run these commands to find the correct paths
which node
which npm
which chromium-browser || which chromium || which google-chrome
```

Update your `.env` with the actual paths returned.

## 5. Install Dependencies
```bash
cd /path/to/your/laravel/app

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install npm dependencies
npm ci --production

# Run migrations
php artisan migrate --force

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Set Up Queue Worker

### Option A: Using Supervisor (Recommended)
```bash
# Install supervisor
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Add this configuration:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/laravel/app/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=your-username
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/laravel/app/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*

# Check status
sudo supervisorctl status
```

### Option B: Using systemd
```bash
sudo nano /etc/systemd/system/laravel-queue.service
```

Add:
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=your-username
WorkingDirectory=/path/to/your/laravel/app
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
sudo systemctl status laravel-queue
```

## 7. Set Up Cron Jobs for Scheduled Scraping

```bash
# Edit crontab
crontab -e
```

Add Laravel scheduler:
```cron
* * * * * cd /path/to/your/laravel/app && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Set Proper Permissions

```bash
cd /path/to/your/laravel/app

# Set ownership
sudo chown -R your-username:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 775 storage bootstrap/cache

# Create scraper logs directory
mkdir -p storage/scraper_logs
chmod 775 storage/scraper_logs
```

## 9. Test Scraper Configuration

```bash
# Test Node.js path
/usr/bin/node --version

# Test Chrome path
/usr/bin/chromium-browser --version || /usr/bin/chromium --version

# Test scraper with small limit
php artisan scraper:run rankings --gender=male --period=2024-12-01 --limit-periods=1 --limit-divisions=1

# Check logs
tail -f storage/logs/laravel.log
```

## 10. DirectAdmin-Specific Settings

### Set up cron via DirectAdmin
1. Log into DirectAdmin panel
2. Go to **Advanced Features** → **Cron Jobs**
3. Add new cron job:
   - **Minute**: *
   - **Hour**: *
   - **Day**: *
   - **Month**: *
   - **Weekday**: *
   - **Command**: `cd /path/to/your/laravel/app && php artisan schedule:run`

### Ensure proper PHP version
1. DirectAdmin → **PHP Version Selector**
2. Select PHP 8.2 or higher
3. Enable required extensions:
   - sqlite3
   - pdo_sqlite
   - mbstring
   - xml
   - curl
   - zip

## 11. Monitoring

### Check queue jobs
```bash
php artisan queue:monitor database

# Or check database
php artisan tinker
>>> DB::table('jobs')->count();
```

### Check scraper runs
```bash
php artisan tinker
>>> App\Models\Scraper\ScraperRun::latest()->first();
```

### Monitor logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/worker.log

# Scraper-specific logs
ls -lh storage/scraper_logs/
```

## Troubleshooting

### Chrome fails to launch
```bash
# Test Chrome manually
/usr/bin/chromium-browser --headless --no-sandbox --dump-dom https://google.com

# If fails, check missing dependencies
ldd /usr/bin/chromium-browser | grep "not found"
```

### Permission errors
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

### Queue jobs not processing
```bash
# Check queue worker is running
ps aux | grep "queue:work"

# Restart queue worker
sudo supervisorctl restart laravel-worker:*
# or
sudo systemctl restart laravel-queue
```
