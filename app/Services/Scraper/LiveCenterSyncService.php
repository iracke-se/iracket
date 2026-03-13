<?php

namespace App\Services\Scraper;

use App\Models\GameMatch;
use App\Models\Scraper\LiveMatchGame;
use App\Models\Scraper\ScraperRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LiveCenterSyncService
{
    protected array $stats = [
        'games_synced' => 0,
        'matches_created' => 0,
        'matches_linked' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    /**
     * Sync live center games to matches table
     */
    public function syncMatches(?int $runId = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();

        $query = LiveMatchGame::where('is_synced', false)
            ->where('game_type', 'singles'); // Only sync singles games

        if ($runId) {
            $query->whereHas('detail', function ($q) use ($runId) {
                $q->where('scraper_run_id', $runId);
            });
        }

        $totalCount = $query->count();
        Log::info("Live Center sync: {$totalCount} games to process");

        if ($run) {
            $run->log('info', "Starting Live Center sync: {$totalCount} games to process");
        }

        $processed = 0;
        $batchSize = 100;

        $query->with('detail')->chunkById($batchSize, function ($games) use (&$processed, $totalCount, $run) {
            foreach ($games as $game) {
                try {
                    $this->syncGame($game);
                    $processed++;
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error("Failed to sync live center game", [
                        'game_id' => $game->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($run) {
                $run->log('info', "Synced: {$processed}/{$totalCount} (Created: {$this->stats['matches_created']}, Linked: {$this->stats['matches_linked']}, Skipped: {$this->stats['skipped']}, Errors: {$this->stats['errors']})");
            }
        });

        if ($run) {
            $run->log('info', "Live Center sync completed: {$this->stats['games_synced']} games synced, {$this->stats['matches_created']} matches created, {$this->stats['matches_linked']} linked");
        }

        return $this->stats;
    }

    /**
     * Sync a single live center game to matches table
     */
    protected function syncGame(LiveMatchGame $game): void
    {
        // Find or create players
        $player1 = $this->findOrCreateUserByName($game->player1_name);
        $player2 = $this->findOrCreateUserByName($game->player2_name);

        if (!$player1 || !$player2) {
            // Skip if names are empty/unparseable
            $game->update(['is_synced' => true]);
            $this->stats['skipped']++;
            return;
        }

        if (!$game->detail) {
            $game->update(['is_synced' => true]);
            $this->stats['skipped']++;
            return;
        }

        $playedAt = $game->detail->played_at;

        // Check if existing match already has this live_match_game_id
        $alreadyLinked = GameMatch::where('live_match_game_id', $game->id)->first();
        if ($alreadyLinked) {
            $game->update(['is_synced' => true, 'synced_match_id' => $alreadyLinked->id]);
            $this->stats['skipped']++;
            return;
        }

        // Find existing match for same players + date
        $parsedDate = Carbon::parse($playedAt)->format('Y-m-d');
        $existingMatch = $this->findMatchingMatch($player1->id, $player2->id, $playedAt);

        if (!$existingMatch) {
            $candidateCount = GameMatch::where(function ($q) use ($player1, $player2) {
                $q->where(function ($q2) use ($player1, $player2) {
                    $q2->where('player1_id', $player1->id)->where('player2_id', $player2->id);
                })->orWhere(function ($q2) use ($player1, $player2) {
                    $q2->where('player1_id', $player2->id)->where('player2_id', $player1->id);
                });
            })->count();

            Log::warning("LiveCenterSync: no match found for game #{$game->id}", [
                'player1' => "{$game->player1_name} (id:{$player1->id})",
                'player2' => "{$game->player2_name} (id:{$player2->id})",
                'date_raw'    => $playedAt,
                'date_parsed' => $parsedDate,
                'matches_for_these_players_total' => $candidateCount,
            ]);
        }

        if ($existingMatch) {
            // Link the existing match to this live center game
            $existingMatch->update(['live_match_game_id' => $game->id]);
            $game->update(['is_synced' => true, 'synced_match_id' => $existingMatch->id]);
            $this->stats['matches_linked']++;
        } else {
            // Create new match from live center data
            $winnerId = null;
            if ($game->winner_name) {
                $winner = $this->findOrCreateUserByName($game->winner_name);
                $winnerId = $winner?->id;
            }

            $newMatch = GameMatch::create([
                'source' => 'scraped',
                'status' => 'confirmed',
                'player1_id' => $player1->id,
                'player2_id' => $player2->id,
                'winner_id' => $winnerId,
                'player1_sets' => $game->player1_sets ?? 0,
                'player2_sets' => $game->player2_sets ?? 0,
                'played_at' => $playedAt,
                'live_match_game_id' => $game->id,
                'created_by' => null,
            ]);

            $game->update(['is_synced' => true, 'synced_match_id' => $newMatch->id]);
            $this->stats['matches_created']++;
        }

        $this->stats['games_synced']++;
    }

    /**
     * Find existing match for same players on same date (either direction)
     */
    protected function findMatchingMatch(int $player1Id, int $player2Id, $playedAt): ?GameMatch
    {
        return GameMatch::where(function ($query) use ($player1Id, $player2Id) {
                $query->where(function ($q) use ($player1Id, $player2Id) {
                    $q->where('player1_id', $player1Id)
                      ->where('player2_id', $player2Id);
                })->orWhere(function ($q) use ($player1Id, $player2Id) {
                    $q->where('player1_id', $player2Id)
                      ->where('player2_id', $player1Id);
                });
            })
            ->whereDate('played_at', Carbon::parse($playedAt)->format('Y-m-d'))
            ->first();
    }

    /**
     * Find or create user by full name
     * First tries to find existing user, then creates if not found
     */
    protected function findOrCreateUser(string $fullName): ?User
    {
        // Try to find existing user first
        $user = $this->findUserByName($fullName);

        if ($user) {
            return $user;
        }

        // Parse name for creation
        $nameParts = $this->parseName($fullName);

        if (!$nameParts) {
            Log::warning("Could not parse name for user creation", ['name' => $fullName]);
            return null;
        }

        $firstName = $nameParts['first_name'];
        $lastName = $nameParts['last_name'];

        // Validate that we have reasonable names
        if (empty($firstName) || empty($lastName) || strlen($firstName) < 2 || strlen($lastName) < 2) {
            Log::warning("Invalid name parts for user creation", [
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
            return null;
        }

        // Generate unique email
        $baseEmail = strtolower(trim($firstName) . '.' . trim($lastName));
        $baseEmail = preg_replace('/[^a-z0-9.]/', '', $baseEmail);
        $email = $baseEmail . '@livecenter.generated';

        // Ensure email is unique by appending number if needed
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = $baseEmail . $counter . '@livecenter.generated';
            $counter++;
        }

        try {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => bcrypt(bin2hex(random_bytes(16))), // Random password
                'email_verified_at' => null, // Not verified
            ]);

            Log::info("Created user from Live Center data", [
                'user_id' => $user->id,
                'name' => $fullName,
                'email' => $email,
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error("Failed to create user from Live Center data", [
                'name' => $fullName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find user by full name (handles "Lastname, Firstname" and "Firstname Lastname" formats)
     * Uses multiple strategies: exact match, partial match, case-insensitive match
     */
    protected function findOrCreateUserByName(?string $fullName): ?User
    {
        if (empty($fullName)) {
            return null;
        }

        // Try to find existing user first
        $user = $this->findUserByName($fullName);
        if ($user) {
            return $user;
        }

        // Create new user
        $nameParts = $this->parseName($fullName);
        if (!$nameParts) {
            return null;
        }

        $email = Str::slug($nameParts['first_name'] . '.' . $nameParts['last_name']) . '@iracket.local';
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = Str::slug($nameParts['first_name'] . '.' . $nameParts['last_name'] . '.' . $counter) . '@iracket.local';
            $counter++;
        }

        return User::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
        ]);
    }

    protected function findUserByName(string $fullName): ?User
    {
        $nameParts = $this->parseName($fullName);

        if (!$nameParts) {
            return null;
        }

        $firstName = $nameParts['first_name'];
        $lastName = $nameParts['last_name'];

        // Strategy 1: Exact match (case-insensitive)
        $user = User::whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
            ->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)])
            ->first();

        if ($user) {
            return $user;
        }

        // Strategy 2: Last name starts with (handles "Berg" matching "Bergenblock")
        $user = User::whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
            ->whereRaw('LOWER(last_name) LIKE ?', [strtolower($lastName) . '%'])
            ->first();

        if ($user) {
            return $user;
        }

        // Strategy 3: Last name contains (more aggressive)
        $user = User::whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
            ->whereRaw('LOWER(last_name) LIKE ?', ['%' . strtolower($lastName) . '%'])
            ->first();

        if ($user) {
            return $user;
        }

        // Strategy 4: Reverse - database name starts with scraped name
        // (handles "Bergenblock" in DB when scraper has "Berg")
        if (strlen($lastName) >= 3) {
            $user = User::whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
                ->whereRaw('LOWER(last_name) LIKE ?', [strtolower($lastName) . '%'])
                ->orWhere(function($q) use ($firstName, $lastName) {
                    $q->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
                      ->whereRaw('? LIKE CONCAT(LOWER(last_name), "%")', [strtolower($lastName)]);
                })
                ->first();

            if ($user) {
                return $user;
            }
        }

        return null;
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
            'games_synced' => 0,
            'matches_created' => 0,
            'matches_linked' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
