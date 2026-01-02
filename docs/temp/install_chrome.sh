#!/bin/bash

# Install Chrome using @puppeteer/browsers CLI (works with Puppeteer 24.x)
# Run this script from your Laravel root directory

set -e  # Exit on any error

echo "=================================="
echo "Installing Chrome for Puppeteer"
echo "=================================="
echo ""

# Get the Laravel root directory
LARAVEL_ROOT=$(pwd)
echo "Laravel root: $LARAVEL_ROOT"
echo ""

# Install @puppeteer/browsers if not already installed
echo "Step 1: Installing @puppeteer/browsers..."
npm install @puppeteer/browsers
echo "✓ @puppeteer/browsers installed"
echo ""

# Download Chrome using @puppeteer/browsers
echo "Step 2: Downloading Chrome stable..."
npx @puppeteer/browsers install chrome@stable --path ./chrome-data
echo "✓ Chrome downloaded"
echo ""

# Find the Chrome binary
echo "Step 3: Locating Chrome binary..."
CHROME_PATH=$(find chrome-data -name "chrome" -type f 2>/dev/null | grep -E "chrome-linux64/chrome$|chrome-linux/chrome$" | head -1)

if [ -z "$CHROME_PATH" ]; then
    echo "ERROR: Chrome binary not found after installation"
    echo "Searching for any chrome executable..."
    find chrome-data -name "chrome" -type f 2>/dev/null || true
    exit 1
fi

# Make it executable
chmod +x "$CHROME_PATH"

# Get absolute path
CHROME_FULL_PATH="$LARAVEL_ROOT/$CHROME_PATH"

echo "✓ Chrome found at: $CHROME_FULL_PATH"
echo ""

# Verify Chrome works
echo "Step 4: Verifying Chrome installation..."
if "$CHROME_FULL_PATH" --version; then
    echo "✓ Chrome is executable and working"
else
    echo "ERROR: Chrome binary is not executable"
    exit 1
fi
echo ""

# Update .env file
echo "Step 5: Updating .env file..."
if grep -q "^SCRAPER_CHROME_PATH=" .env; then
    # Use | as delimiter since path contains /
    sed -i.bak "s|^SCRAPER_CHROME_PATH=.*|SCRAPER_CHROME_PATH=\"$CHROME_FULL_PATH\"|" .env
    echo "✓ Updated existing SCRAPER_CHROME_PATH in .env"
else
    echo "" >> .env
    echo "SCRAPER_CHROME_PATH=\"$CHROME_FULL_PATH\"" >> .env
    echo "✓ Added SCRAPER_CHROME_PATH to .env"
fi
echo ""

# Clear config cache
echo "Step 6: Clearing config cache..."
php artisan config:clear
echo "✓ Config cache cleared"
echo ""

echo "=================================="
echo "Installation Complete!"
echo "=================================="
echo ""
echo "Chrome installed at:"
echo "$CHROME_FULL_PATH"
echo ""
echo "Run this to verify:"
echo "  php artisan scraper:check"
echo ""
