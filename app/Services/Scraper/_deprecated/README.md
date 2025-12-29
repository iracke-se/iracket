# Deprecated Scrapers

This folder contains scrapers that have been deprecated and removed from the pipeline.

## LiveCenterScraper.php.bak

**Deprecated on:** 2025-12-23

**Reason:** The LiveCenter scraper was pulling team/club matches (e.g., "Vetlanda BTK" vs "Ängby SK: A2") instead of individual player matches. These team matches cannot be synced to the player match database as they don't represent individual player vs player games.

**Source URL:** `https://www.profixio.com/fx/livecenter/`

**What it scraped:**
- Team competitions and league matches between clubs
- Not suitable for individual player rankings

**Future consideration:** May be useful if we want to track team/club competitions separately.

## MatchSyncService.php.bak

**Deprecated on:** 2025-12-23

**Reason:** This service was designed to sync matches scraped from LiveCenter. Since LiveCenter is no longer being scraped, this service is no longer needed.

**TODO:** When we find the correct source for individual player matches, we'll need to create a new match sync service or adapt this one.
