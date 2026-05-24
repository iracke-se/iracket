# Scraper Setup Guide

This document explains how to set up the web scraper system for different environments.

## Overview

The scraper uses [Spatie Browsershot](https://github.com/spatie/browsershot) which requires:
- Node.js
- npm
- Puppeteer (npm package)
- Chromium/Chrome browser

## Requirements

### System Dependencies

| Dependency | Purpose |
|------------|---------|
| Node.js 18+ | JavaScript runtime for Puppeteer |
| npm | Package manager for installing Puppeteer |
| Chromium/Chrome | Headless browser for web scraping |
| Puppeteer | Node.js library to control Chrome |

## Environment Configuration

Add these variables to your `.env` file:

```env
# Scraper Browser Configuration
SCRAPER_NODE_BINARY=/usr/local/bin/node
SCRAPER_NPM_BINARY=/usr/local/bin/npm
SCRAPER_CHROME_PATH=/usr/bin/chromium
SCRAPER_TIMEOUT=60000
SCRAPER_HEADLESS=true

# Scraper Target URL
SCRAPER_MAIN_URL=https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php

# Scraper Scheduling
SCRAPER_SCHEDULE_RANKINGS=true
SCRAPER_SCHEDULE_PLAYERS=true
SCRAPER_SCHEDULE_SERIES=false
SCRAPER_SCHEDULE_LIVECENTER=false

# Scraper Queue
SCRAPER_QUEUE_CONNECTION=database
SCRAPER_QUEUE_NAME=scraper

# Scraper Logging
SCRAPER_LOG_CHANNEL=scraper
SCRAPER_DETAILED_LOG=true
```

## DDEV Development Setup

### Automatic Setup (Recommended)

Add these hooks to `.ddev/config.yaml`:

```yaml
hooks:
  post-start:
    - exec: "npm install puppeteer"
    - exec: "sudo apt-get update && sudo apt-get install -y chromium"
```

After adding the hooks, restart ddev:

```bash
ddev restart
```

### Manual Setup

If you prefer manual setup or need to reinstall:

```bash
# Install puppeteer
ddev exec npm install puppeteer

# Install Chromium
ddev exec sudo apt-get update
ddev exec sudo apt-get install -y chromium
```

### Verify Installation

```bash
# Check Chromium is installed
ddev exec which chromium
# Should output: /usr/bin/chromium

# Check Node.js
ddev exec node -v

# Check npm
ddev exec npm -v

# Verify puppeteer
ddev exec npm list puppeteer
```

### DDEV Environment Variables

For ddev, use these paths in `.env`:

```env
SCRAPER_NODE_BINARY=/usr/local/bin/node
SCRAPER_NPM_BINARY=/usr/local/bin/npm
SCRAPER_CHROME_PATH=/usr/bin/chromium
```

## Production Server Setup

### Ubuntu/Debian

```bash
# Install Node.js (via NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install Chromium
sudo apt-get update
sudo apt-get install -y chromium-browser

# Or for headless Chrome
sudo apt-get install -y google-chrome-stable

# Install project dependencies
cd /path/to/project
npm install puppeteer
```

### CentOS/RHEL

```bash
# Install Node.js
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo yum install -y nodejs

# Install Chromium
sudo yum install -y chromium

# Install puppeteer
cd /path/to/project
npm install puppeteer
```

### macOS (Local Development)

```bash
# Install Node.js via Homebrew
brew install node

# Chrome is usually already installed, or:
brew install --cask google-chrome

# Install puppeteer
npm install puppeteer
```

Update `.env` for macOS:

```env
SCRAPER_NODE_BINARY=/usr/local/bin/node
SCRAPER_NPM_BINARY=/usr/local/bin/npm
SCRAPER_CHROME_PATH=/Applications/Google Chrome.app/Contents/MacOS/Google Chrome
```

## Docker Production Setup

If running in Docker without ddev, add to your Dockerfile:

```dockerfile
# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Chromium and dependencies
RUN apt-get update && apt-get install -y \
    chromium \
    libx11-xcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxi6 \
    libxtst6 \
    libnss3 \
    libcups2 \
    libxss1 \
    libxrandr2 \
    libasound2 \
    libpangocairo-1.0-0 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libgtk-3-0 \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Install puppeteer
WORKDIR /var/www/html
COPY package.json ./
RUN npm install puppeteer
```

## Important Notes

### Sandbox Mode

In Docker environments, Chrome needs to run without sandbox mode. This is handled automatically by the scraper with `->noSandbox()`.

### Memory Requirements

Headless Chrome can be memory-intensive. Ensure your server has:
- Minimum: 1GB RAM
- Recommended: 2GB+ RAM

### Timeouts

The default timeout is 60 seconds. For slow connections or large scrapes, increase it:

```env
SCRAPER_TIMEOUT=120000  # 2 minutes
```

## Testing the Setup

### Run Integration Tests

```bash
# DDEV
ddev exec ./vendor/bin/pest tests/Unit/Scraper/ScraperIntegrationTest.php --filter="Rankings Scraper" --configuration=phpunit.scraper.xml

# Direct
./vendor/bin/pest tests/Unit/Scraper/ScraperIntegrationTest.php --filter="Rankings Scraper"
```

### Manual Scraper Test

```bash
# Run rankings scraper manually
ddev exec php artisan scraper:run rankings --gender=male --limit-periods=1 --limit-divisions=1
```

## Scheduled Scraping

The scrapers run on schedule via Laravel's task scheduler. Ensure cron is configured:

```bash
# Add to crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Default schedule (configurable in `config/scraper.php`):
- **Rankings**: Weekly on Sundays at 2:00 AM
- **Players**: Monthly on the 1st at 3:00 AM
- **Series**: Weekly on Mondays at 4:00 AM (disabled by default)
- **Live Center**: Daily at 5:00 AM (disabled by default)

## Troubleshooting

### Common Issues

#### "Cannot find module 'puppeteer'"

```bash
npm install puppeteer
```

#### "Browser was not found at the configured executablePath"

Check your `SCRAPER_CHROME_PATH` is correct:

```bash
# Find Chromium location
which chromium
# or
which chromium-browser
# or
which google-chrome
```

#### "Failed to move to new namespace: Operation not permitted"

This is a sandbox error in Docker. The scraper handles this automatically with `->noSandbox()`, but ensure your scraper classes include this method call.

#### Empty Results (periods: 0, divisions: 0)

Browsershot is stateless - each `evaluate()` call creates a fresh browser session. The scraper handles this by combining operations or navigating directly to the target URL.

### Checking Logs

```bash
# View scraper logs
tail -f storage/logs/scraper/scraper.log

# Check Laravel logs
tail -f storage/logs/laravel.log
```

## Configuration Reference

See `config/scraper.php` for all available options:

- `main_url` - Base URL for scraping
- `browser.*` - Browser configuration (timeouts, paths)
- `retry.*` - Retry settings for failed operations
- `delays.*` - Delays between operations to avoid rate limiting
- `selectors.*` - CSS selectors for navigation
- `schedule.*` - Scheduling configuration for each scraper type

## Security Considerations

1. **Rate Limiting**: The scraper includes delays between requests. Respect the target site's terms of service.

2. **Credentials**: Never store credentials in version control. Use environment variables.

3. **Sandbox Mode**: Running Chrome without sandbox (`--no-sandbox`) reduces security. Only use in trusted environments (Docker containers, dedicated servers).

4. **Network Access**: Ensure your server can access the target URLs. Some hosts may block outbound requests.
