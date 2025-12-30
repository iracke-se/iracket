# Chromium Installation Guide (User-Level, No Sudo)

## Problem

Puppeteer 24.x no longer auto-downloads Chromium to `.local-chromium/` directory. This guide provides solutions for installing Chrome at user-level without sudo access.

## Solution 1: Use @puppeteer/browsers CLI (Recommended)

This is the modern approach that works with Puppeteer 24.x+.

### Steps

```bash
# 1. Navigate to your Laravel root
cd /home/iracket/domains/dev.iracket.se/public_html

# 2. Make script executable
chmod +x docs/temp/install_chrome.sh

# 3. Run installation script
./docs/temp/install_chrome.sh

# 4. Verify installation
php artisan scraper:check
```

### What the Script Does

1. Installs `@puppeteer/browsers` package
2. Downloads Chrome stable to `./chrome-data/` directory
3. Finds the Chrome binary path
4. Makes it executable
5. Updates `SCRAPER_CHROME_PATH` in .env
6. Clears config cache

### Expected Result

```
✓ Chrome: Google Chrome 131.x.x
```

---

## Solution 2: Downgrade to Puppeteer 19.x (Alternative)

If Solution 1 fails, use Puppeteer 19.x which still bundles Chromium.

```bash
cd /home/iracket/domains/dev.iracket.se/public_html

# Uninstall current Puppeteer
npm uninstall puppeteer

# Install Puppeteer 19.x (last version with bundled Chromium)
npm install puppeteer@19.11.1

# Find Chrome path
CHROME_PATH=$(find node_modules/puppeteer -name "chrome" -type f 2>/dev/null | grep -E "chrome-linux/chrome$" | head -1)
CHROME_FULL_PATH=$(pwd)/$CHROME_PATH

# Make executable
chmod +x "$CHROME_FULL_PATH"

# Update .env
sed -i.bak "s|^SCRAPER_CHROME_PATH=.*|SCRAPER_CHROME_PATH=\"$CHROME_FULL_PATH\"|" .env

# Test
"$CHROME_FULL_PATH" --version
php artisan config:clear
php artisan scraper:check
```

---

## Solution 3: Manual Chrome Download

If both automated methods fail, download Chrome manually:

```bash
cd /home/iracket/domains/dev.iracket.se/public_html

# Create directory
mkdir -p chrome-manual

# Download Chrome for Linux
wget https://storage.googleapis.com/chrome-for-testing-public/131.0.6778.87/linux64/chrome-linux64.zip -O chrome.zip

# Extract
unzip chrome.zip -d chrome-manual

# Find binary
CHROME_PATH=$(find chrome-manual -name "chrome" -type f | head -1)
CHROME_FULL_PATH=$(pwd)/$CHROME_PATH

# Make executable
chmod +x "$CHROME_FULL_PATH"

# Update .env
sed -i.bak "s|^SCRAPER_CHROME_PATH=.*|SCRAPER_CHROME_PATH=\"$CHROME_FULL_PATH\"|" .env

# Verify
"$CHROME_FULL_PATH" --version
php artisan config:clear
php artisan scraper:check
```

---

## Troubleshooting

### Chrome Binary Not Executable

```bash
# Find the binary
find . -name "chrome" -type f 2>/dev/null

# Make it executable
chmod +x /path/to/chrome

# Test
/path/to/chrome --version
```

### Missing System Libraries

If Chrome fails with "error while loading shared libraries":

```bash
# Check missing libraries
ldd /path/to/chrome | grep "not found"
```

**Common missing libraries on DirectAdmin:**
- `libgbm.so.1`
- `libasound.so.2`
- `libatk-bridge-2.0.so.0`

**Solution:** Contact hosting provider to install these system libraries, OR use a pre-built Chrome binary for serverless environments:

```bash
npm install @sparticuz/chromium
# This package includes all dependencies bundled
```

### Chrome Version Mismatch

If Puppeteer complains about Chrome version:

```bash
# Check Puppeteer's expected Chrome version
npx @puppeteer/browsers install chrome@$(node -p "require('puppeteer-core/package.json').version")
```

---

## Verification

After installation, run the environment check:

```bash
php artisan scraper:check --verbose
```

Expected output:

```
╔════════════════════════════════════════════════════════╗
║          SCRAPER ENVIRONMENT CHECK                     ║
╚════════════════════════════════════════════════════════╝

  ✓ PHP Version: 8.4.15
  ⚠ PHP Extensions: Missing - pcntl (optional)
  ✓ Node.js: v24.12.0 at /home/iracket/.nvm/versions/node/v24.12.0/bin/node
  ✓ npm: 11.6.2 at /home/iracket/.nvm/versions/node/v24.12.0/bin/npm
  ✓ Chrome: Google Chrome 131.0.6778.87
  ✓ Browsershot: Package installed, Puppeteer 24.34.0
  ✓ Database: Connected (mariadb)
  ✓ Storage Permissions: All directories writable
  ✓ Environment Variables: All required variables set
  ✓ Queue: Configured with 'database' driver
  ⚠ Queue Worker: Not running
  ✓ Database Tables: All scraper tables exist
  ✓ Scraper Test: Successfully fetched test page

╔════════════════════════════════════════════════════════╗
║                    SUMMARY                             ║
╚════════════════════════════════════════════════════════╝

  Passed:   11
  Failed:   0
  Warnings: 2

⚠  Scraper is functional but has warnings.
   Consider addressing warnings for optimal performance.
```

---

## Next Steps After Chrome Installation

Once Chrome is installed and verified:

1. **Set up Supervisor** for queue workers (see [PRODUCTION_QUEUE_SETUP.md](PRODUCTION_QUEUE_SETUP.md))
2. **Test scraper** with limit flags:
   ```bash
   php artisan scraper:queue rankings 2024-12 --limit-periods=1 --limit-divisions=1
   ```
3. **Start queue worker**:
   ```bash
   php artisan queue:work --verbose
   ```
4. **Monitor logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## DirectAdmin-Specific Notes

### File Permissions

Ensure Laravel storage is writable:

```bash
chmod -R 775 storage bootstrap/cache
```

### Cron Jobs

After Supervisor is set up, add Laravel scheduler to cron:

**DirectAdmin → Advanced Features → Cron Jobs**
- Command: `cd /home/iracket/domains/dev.iracket.se/public_html && php artisan schedule:run`
- Schedule: `* * * * *`

### Memory Limits

If scraper fails with memory errors, increase PHP memory limit:

**DirectAdmin → PHP Settings**
- Set `memory_limit = 512M` or higher

Or add to `.env`:
```env
PHP_MEMORY_LIMIT=512M
```

---

## Support

If all methods fail, you may need to:

1. Contact hosting provider to install Chromium system-wide
2. Use a different hosting solution with Chrome pre-installed
3. Use a headless browser service (e.g., BrowserStack, Puppeteer as a service)
