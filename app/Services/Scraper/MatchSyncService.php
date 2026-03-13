<?php

namespace App\Services\Scraper;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\User;
use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScraperRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MatchSyncService
{
    protected array $stats = [
        'created' => 0,
        'updated' => 0,
        'comments_migrated' => 0,
        'manual_matches_replaced' => 0,
        'manual_matches_marked_unofficial' => 0,
        'errors' => 0,
    ];

    // Performance cache: avoid N+1 DB queries during sync
    protected array $usersCache = [];   // keyed by "firstname|lastname" → User
    protected array $emailsCache = [];  // keyed by email → true

    /**
     * Sync matches from scraped data
     */
    public function syncMatches(?int $runId = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();

        $query = ScrapedMatch::where('is_synced', false);

        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $totalCount = $query->count();
        Log::info("Syncing {$totalCount} matches");

        if ($run) {
            $run->log('info', "Starting match sync: {$totalCount} matches to process");
        }

        // Pre-load all users into memory to avoid N+1 queries
        User::select(['id', 'first_name', 'last_name', 'email'])->chunk(1000, function ($users) {
            foreach ($users as $user) {
                $key = strtolower($user->first_name) . '|' . strtolower($user->last_name);
                if (!isset($this->usersCache[$key])) {
                    $this->usersCache[$key] = $user;
                }
                $this->emailsCache[$user->email] = true;
            }
        });

        $processed = 0;
        $batchSize = 100;

        // Process in batches for better performance and progress tracking
        $query->chunk($batchSize, function ($scrapedMatches) use (&$processed, $totalCount, $run) {
            foreach ($scrapedMatches as $scrapedMatch) {
                try {
                    $this->syncMatch($scrapedMatch);
                    $processed++;
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error("Failed to sync match", [
                        'scraped_match_id' => $scrapedMatch->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log progress after each batch
            if ($run) {
                $run->log('info', "Synced matches: {$processed}/{$totalCount} (Created: {$this->stats['created']}, Comments migrated: {$this->stats['comments_migrated']}, Errors: {$this->stats['errors']})");
            }
        });

        // After syncing all scraped matches, mark remaining manual matches as unofficial
        if ($run) {
            $run->log('info', "Marking unofficial matches...");
        }
        $this->markUnofficialMatches();

        if ($run) {
            $run->log('info', "Match sync completed: {$this->stats['created']} created, {$this->stats['comments_migrated']} comments migrated, {$this->stats['manual_matches_marked_unofficial']} marked unofficial");
        }

        return $this->stats;
    }

    /**
     * Sync a single scraped match
     */
    protected function syncMatch(ScrapedMatch $scrapedMatch): void
    {
        // Parse player IDs from scraped data
        $players = $this->parsePlayerIds($scrapedMatch);

        if (!$players) {
            $scrapedMatch->update(['is_synced' => true]);
            return;
        }

        // Check if a scraped GameMatch already exists for same players + date
        // (happens when both players were scraped independently from the rankings popup)
        $existingScrapedMatch = GameMatch::where('source', 'scraped')
            ->whereDate('played_at', Carbon::parse($scrapedMatch->played_at)->format('Y-m-d'))
            ->where(function ($q) use ($players) {
                $q->where(function ($q2) use ($players) {
                    $q2->where('player1_id', $players['player1_id'])
                       ->where('player2_id', $players['player2_id']);
                })->orWhere(function ($q2) use ($players) {
                    $q2->where('player1_id', $players['player2_id'])
                       ->where('player2_id', $players['player1_id']);
                });
            })
            ->first();

        if ($existingScrapedMatch) {
            // Match already created from the other player's scrape — just link and enrich
            $isPlayer1 = $existingScrapedMatch->player1_id === $players['player1_id'];
            $matchPoints = $scrapedMatch->match_points;
            $updateData = $isPlayer1
                ? ['player1_match_points' => $matchPoints]
                : ['player2_match_points' => $matchPoints];

            $existingScrapedMatch->update($updateData);

            $scrapedMatch->update([
                'is_synced' => true,
                'synced_match_id' => $existingScrapedMatch->id,
            ]);

            $this->stats['updated']++;
            return;
        }

        // Check if manual match exists for same players on same date
        $existingManualMatch = $this->findMatchingManualMatch(
            $players['player1_id'],
            $players['player2_id'],
            Carbon::parse($scrapedMatch->played_at)
        );

        // Parse score to get player1_sets and player2_sets
        $sets = $this->parseScore($scrapedMatch->score);

        // Create official scraped match, storing ranking points for player1 (the scraped player)
        $officialMatch = GameMatch::create([
            'source' => 'scraped',
            'status' => 'confirmed',
            'player1_id' => $players['player1_id'],
            'player2_id' => $players['player2_id'],
            'winner_id' => $players['winner_id'],
            'player1_sets' => $sets['player1_sets'],
            'player2_sets' => $sets['player2_sets'],
            'player1_match_points' => $scrapedMatch->match_points,
            'played_at' => $scrapedMatch->played_at,
            'created_by' => null,
        ]);

        // If manual match exists, migrate comments and soft delete it
        if ($existingManualMatch) {
            $player1CommentCount = count($existingManualMatch->player1_comments ?? []);
            $player2CommentCount = count($existingManualMatch->player2_comments ?? []);
            $totalComments = $player1CommentCount + $player2CommentCount;

            $this->migrateCommentsAndReplace($existingManualMatch, $officialMatch);
            $this->stats['manual_matches_replaced']++;

            if ($totalComments > 0) {
                $this->stats['comments_migrated'] += $totalComments;
            }
        }

        $scrapedMatch->update([
            'is_synced' => true,
            'synced_match_id' => $officialMatch->id,
        ]);

        $this->stats['created']++;
    }

    /**
     * Find manual match that matches scraped data
     */
    protected function findMatchingManualMatch(
        int $player1Id,
        int $player2Id,
        Carbon $playedAt
    ): ?GameMatch {
        // Find match on same date with same players (either direction)
        return GameMatch::where('source', 'player_added')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($player1Id, $player2Id) {
                $query->where(function ($q) use ($player1Id, $player2Id) {
                    $q->where('player1_id', $player1Id)
                      ->where('player2_id', $player2Id);
                })->orWhere(function ($q) use ($player1Id, $player2Id) {
                    $q->where('player1_id', $player2Id)
                      ->where('player2_id', $player1Id);
                });
            })
            ->whereDate('played_at', $playedAt->format('Y-m-d'))
            ->first();
    }

    /**
     * Migrate comments from manual match to official match and soft delete
     */
    protected function migrateCommentsAndReplace(GameMatch $manualMatch, GameMatch $officialMatch): void
    {
        // Migrate comments from manual match to official match
        // Comments are stored as JSON arrays in player1_comments and player2_comments
        $player1Comments = $manualMatch->player1_comments ?? [];
        $player2Comments = $manualMatch->player2_comments ?? [];

        if (!empty($player1Comments) || !empty($player2Comments)) {
            $officialMatch->update([
                'player1_comments' => $player1Comments,
                'player2_comments' => $player2Comments,
            ]);
        }

        // Mark manual match as replaced and soft delete
        $manualMatch->update([
            'replaced_by_match_id' => $officialMatch->id,
        ]);

        $manualMatch->delete(); // Soft delete

        Log::info("Replaced manual match with official match", [
            'manual_match_id' => $manualMatch->id,
            'official_match_id' => $officialMatch->id,
            'player1_comments_migrated' => count($player1Comments),
            'player2_comments_migrated' => count($player2Comments),
        ]);
    }

    /**
     * Mark all remaining manual matches (not matched by scraper) as unofficial
     */
    protected function markUnofficialMatches(): void
    {
        // Find all player-added matches that:
        // 1. Are not deleted (not replaced by scraper)
        // 2. Are not already marked unofficial
        // 3. Are older than a threshold (e.g., scraper should have seen them)

        $thresholdDate = now()->subDays(7); // Matches older than 7 days should be in scraper data

        $unofficialCount = GameMatch::where('source', 'player_added')
            ->whereNull('deleted_at')
            ->where('is_unofficial', false)
            ->where('played_at', '<', $thresholdDate)
            ->update(['is_unofficial' => true]);

        $this->stats['manual_matches_marked_unofficial'] = $unofficialCount;

        if ($unofficialCount > 0) {
            Log::info("Marked {$unofficialCount} manual matches as unofficial");
        }
    }

    /**
     * Parse player IDs from scraped match
     */
    protected function parsePlayerIds(ScrapedMatch $scrapedMatch): ?array
    {
        // Find or create users by player names
        $player1 = $this->findOrCreateUserByName($scrapedMatch->player1_name ?? $scrapedMatch->player_name);
        $player2 = $this->findOrCreateUserByName($scrapedMatch->player2_name ?? $scrapedMatch->opponent_name);

        if (!$player1 || !$player2) {
            return null;
        }

        $winnerId = null;
        $player1Name = $scrapedMatch->player1_name ?? $scrapedMatch->player_name;
        $player2Name = $scrapedMatch->player2_name ?? $scrapedMatch->opponent_name;

        if ($scrapedMatch->winner) {
            if (stripos($scrapedMatch->winner, $player1Name) !== false) {
                $winnerId = $player1->id;
            } elseif (stripos($scrapedMatch->winner, $player2Name) !== false) {
                $winnerId = $player2->id;
            }
        } elseif ($scrapedMatch->result === 'W') {
            // Rankings popup format: result=W means player1 (player_name) won
            $winnerId = $player1->id;
        } elseif ($scrapedMatch->result === 'L') {
            $winnerId = $player2->id;
        }

        return [
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'winner_id' => $winnerId,
        ];
    }

    /**
     * Find or create user by full name (uses in-memory cache to avoid N+1)
     */
    protected function findOrCreateUserByName(?string $fullName): ?User
    {
        if (empty($fullName)) {
            return null;
        }

        $nameParts = $this->parseName($fullName);

        if (!$nameParts) {
            return null;
        }

        $key = strtolower($nameParts['first_name']) . '|' . strtolower($nameParts['last_name']);

        if (isset($this->usersCache[$key])) {
            return $this->usersCache[$key];
        }

        // Not in cache — check DB (could have been created by a concurrent sync or before cache init)
        $user = User::where('first_name', $nameParts['first_name'])
            ->where('last_name', $nameParts['last_name'])
            ->first();

        if (!$user) {
            $email = $this->generateEmail($nameParts['first_name'], $nameParts['last_name']);
            $user = User::create([
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
            ]);
            $this->emailsCache[$email] = true;
        }

        $this->usersCache[$key] = $user;

        return $user;
    }

    /**
     * Generate a unique email for a new user (uses in-memory cache to avoid N+1)
     */
    protected function generateEmail(string $firstName, string $lastName): string
    {
        $baseEmail = Str::slug($firstName . '.' . $lastName) . '@iracket.local';
        $email = $baseEmail;
        $counter = 1;

        while (isset($this->emailsCache[$email])) {
            $email = Str::slug($firstName . '.' . $lastName . '.' . $counter) . '@iracket.local';
            $counter++;
        }

        return $email;
    }

    /**
     * Parse name string into first and last name
     */
    protected function parseName(string $name): ?array
    {
        $name = trim($name);

        if (empty($name)) {
            return null;
        }

        // Handle "Surname, FirstName" format
        if (str_contains($name, ',')) {
            $parts = explode(',', $name);
            return [
                'first_name' => trim($parts[1] ?? ''),
                'last_name' => trim($parts[0] ?? ''),
            ];
        }

        // Handle "FirstName Surname" format
        $parts = explode(' ', $name);
        if (count($parts) >= 2) {
            $lastName = array_pop($parts);
            $firstName = implode(' ', $parts);
            return [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        }

        return null;
    }

    protected function resetStats(): void
    {
        $this->stats = [
            'created' => 0,
            'updated' => 0,
            'comments_migrated' => 0,
            'manual_matches_replaced' => 0,
            'manual_matches_marked_unofficial' => 0,
            'errors' => 0,
        ];

        $this->usersCache = [];
        $this->emailsCache = [];
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Parse score string to count game wins
     * Examples: "9-11, 13-11, 9-11, 11-8, 11-13" or "3-1" (match result)
     */
    protected function parseScore(string $score): array
    {
        // Default to 0-0
        $result = [
            'player1_sets' => 0,
            'player2_sets' => 0,
        ];

        // Clean up score
        $score = trim($score);
        if (empty($score)) {
            return $result;
        }

        // If score contains commas, it's individual game scores like "9-11, 13-11, 9-11"
        if (str_contains($score, ',')) {
            $games = explode(',', $score);

            foreach ($games as $game) {
                $game = trim($game);
                if (preg_match('/(\d+)-(\d+)/', $game, $matches)) {
                    $score1 = (int) $matches[1];
                    $score2 = (int) $matches[2];

                    if ($score1 > $score2) {
                        $result['player1_sets']++;
                    } elseif ($score2 > $score1) {
                        $result['player2_sets']++;
                    }
                }
            }
        } else {
            // Handle simple match result formats like "3 - 2" or "6-3"
            if (preg_match('/(\d+)\s*-\s*(\d+)/', $score, $matches)) {
                $result['player1_sets'] = (int) $matches[1];
                $result['player2_sets'] = (int) $matches[2];
            }
        }

        return $result;
    }
}
