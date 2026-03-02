#!/usr/bin/env python3
"""
Test script to see what dates are available in the Live Center dropdown
"""
import asyncio
import sys
from playwright.async_api import async_playwright

async def test_dates():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=False)  # Show browser
        page = await browser.new_page()

        # Login and navigate
        print("Navigating to Live Center...")
        await page.goto('https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT')
        await page.wait_for_timeout(2000)

        await page.goto('https://www.profixio.com/fx/livecenter/')
        await page.wait_for_timeout(2000)

        # Set division to All
        await page.evaluate("""
            () => {
                const divSelect = document.getElementById('filter4_id');
                if (divSelect) {
                    divSelect.value = '';
                    divSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        """)
        await page.wait_for_timeout(2000)

        # Get ALL dates from dropdown
        all_dates = await page.evaluate("""
            () => {
                const dateSelect = document.getElementById('filter1_id');
                if (!dateSelect) return {error: 'Dropdown not found'};

                const dates = [];
                for (let i = 0; i < dateSelect.options.length; i++) {
                    dates.push(dateSelect.options[i].value);
                }

                return {
                    total: dates.length,
                    first_10: dates.slice(0, 10),
                    last_10: dates.slice(-10),
                    all_dates: dates
                };
            }
        """)

        print(f"\n=== TOTAL DATES IN DROPDOWN: {all_dates['total']} ===\n")
        print(f"First 10 dates: {all_dates['first_10']}")
        print(f"Last 10 dates: {all_dates['last_10']}")

        # Filter for 2025
        dates_2025 = [d for d in all_dates['all_dates'] if d.startswith('2025-')]
        print(f"\n=== DATES FOR 2025: {len(dates_2025)} ===")
        print(f"2025 dates: {dates_2025}")

        # Filter for 2024
        dates_2024 = [d for d in all_dates['all_dates'] if d.startswith('2024-')]
        print(f"\n=== DATES FOR 2024: {len(dates_2024)} ===")
        print(f"First 10 from 2024: {dates_2024[:10]}")
        print(f"Last 10 from 2024: {dates_2024[-10:]}")

        # Keep browser open
        input("\nPress Enter to close browser...")
        await browser.close()

if __name__ == "__main__":
    asyncio.run(test_dates())
