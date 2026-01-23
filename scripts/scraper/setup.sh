#!/bin/bash
# Setup Python scraper environment

echo "Setting up Python Playwright scraper environment..."

# Create virtual environment
echo "Creating virtual environment..."
python3 -m venv venv

# Activate virtual environment
echo "Activating virtual environment..."
source venv/bin/activate

# Install dependencies
echo "Installing Python dependencies..."
pip install --upgrade pip
pip install -r requirements.txt

# Install Playwright browsers
echo "Installing Playwright Chromium browser..."
playwright install chromium

echo ""
echo "✅ Setup complete!"
echo ""
echo "To test the scraper, run:"
echo "  source venv/bin/activate"
echo "  python3 rankings_popup_scraper.py --year 2025 --month 12 --gender m --limit 1"
