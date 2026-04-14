<?php

namespace App\Services\Scraper;

use App\Models\Club;
use App\Models\User;
use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncService
{
    protected array $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    // Performance optimization: Cache clubs and emails in memory
    protected array $clubsCache = [];
    protected array $existingEmails = [];
    protected bool $cacheInitialized = false;

    /**
     * Sync players from scraped data to users table
     */
    public function syncPlayers(?int $runId = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();
        $this->initializeCache();

        $query = ScrapedPlayer::where('is_synced', false);

        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $totalCount = $query->count();
        Log::info("Syncing {$totalCount} players");

        if ($run) {
            $run->log('info', "Starting player sync: {$totalCount} players to process");
        }

        $processed = 0;
        $batchSize = 100;

        // Process in batches for better performance and progress tracking
        // Use chunkById to safely iterate while modifying is_synced flag
        $query->chunkById($batchSize, function ($players) use (&$processed, $totalCount, $run) {
            foreach ($players as $player) {
                try {
                    $this->syncPlayer($player);
                    $processed++;
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error("Failed to sync player: {$player->full_name}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log progress after each batch
            if ($run) {
                $run->log('info', "Synced players: {$processed}/{$totalCount} (Created: {$this->stats['created']}, Updated: {$this->stats['updated']}, Errors: {$this->stats['errors']})");
            }
        });

        if ($run) {
            $run->log('info', "Player sync completed: {$this->stats['created']} created, {$this->stats['updated']} updated, {$this->stats['errors']} errors");
        }

        return $this->stats;
    }

    /**
     * Initialize performance caches
     */
    protected function initializeCache(): void
    {
        if ($this->cacheInitialized) {
            return;
        }

        // Load all clubs into memory indexed by slug
        $this->clubsCache = Club::all()->keyBy('slug')->toArray();

        // Load all existing emails into a set for fast lookups
        $this->existingEmails = User::pluck('email')->flip()->toArray();

        $this->cacheInitialized = true;

        Log::info('Performance cache initialized', [
            'clubs' => count($this->clubsCache),
            'emails' => count($this->existingEmails),
        ]);
    }

    /**
     * Sync rankings from scraped data to users table
     */
    public function syncRankings(?int $runId = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();
        $this->initializeCache();

        $query = ScrapedRanking::where('is_synced', false);

        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $totalCount = $query->count();
        Log::info("Syncing {$totalCount} rankings");

        if ($run) {
            $run->log('info', "Starting rankings sync: {$totalCount} rankings to process");
        }

        $processed = 0;
        $batchSize = 100;

        // Process in batches for better performance and progress tracking
        // Use chunkById to safely iterate while modifying is_synced flag
        $query->chunkById($batchSize, function ($rankings) use (&$processed, $totalCount, $run) {
            foreach ($rankings as $ranking) {
                try {
                    $this->syncRanking($ranking);
                    $processed++;
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error("Failed to sync ranking: {$ranking->name}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log progress after each batch
            if ($run) {
                $run->log('info', "Synced rankings: {$processed}/{$totalCount} (Created: {$this->stats['created']}, Updated: {$this->stats['updated']}, Errors: {$this->stats['errors']})");
            }
        });

        if ($run) {
            $run->log('info', "Rankings sync completed: {$this->stats['created']} created, {$this->stats['updated']} updated, {$this->stats['errors']} errors");
        }

        return $this->stats;
    }

    /**
     * Create monthly rankings from scraped rankings
     * Takes the latest ranking for each user in a given month
     */
    public function createMonthlyRankings(?int $runId = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();

        $query = ScrapedRanking::where('is_synced', true)
            ->whereNotNull('synced_user_id');

        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        if ($run) {
            $run->log('info', 'Starting monthly rankings creation from scraped data');
        }

        // Group by user and period, get the latest ranking for each
        $rankings = $query->select('synced_user_id', 'period', DB::raw('MAX(ranking_date) as latest_date'))
            ->groupBy('synced_user_id', 'period')
            ->get();

        $totalCount = $rankings->count();
        if ($run) {
            $run->log('info', "Found {$totalCount} unique user/period combinations to process");
        }

        $processed = 0;
        foreach ($rankings as $grouping) {
            try {
                // Get the full ranking record for this user's latest ranking in this period
                $ranking = ScrapedRanking::where('synced_user_id', $grouping->synced_user_id)
                    ->where('period', $grouping->period)
                    ->where('ranking_date', $grouping->latest_date)
                    ->first();

                if ($ranking) {
                    // Parse period (format: "2025-12")
                    [$year, $month] = explode('-', $ranking->period);

                    // Create or update monthly ranking
                    \App\Models\MonthlyRanking::updateOrCreate(
                        [
                            'user_id' => $ranking->synced_user_id,
                            'year' => (int) $year,
                            'month' => (int) $month,
                        ],
                        [
                            'rank' => $ranking->position,
                            'points' => $ranking->points,
                            'points_change' => $this->parsePointsChange($ranking->points_diff),
                            'ranking_date' => $ranking->ranking_date,
                        ]
                    );

                    $this->stats['created']++;
                }

                $processed++;

                // Log progress every 100 items
                if ($processed % 100 === 0 && $run) {
                    $run->log('info', "Created monthly rankings: {$processed}/{$totalCount}");
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error("Failed to create monthly ranking", [
                    'user_id' => $grouping->synced_user_id,
                    'period' => $grouping->period,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($run) {
            $run->log('info', "Monthly rankings creation completed: {$this->stats['created']} created, {$this->stats['errors']} errors");
        }

        return $this->stats;
    }

    /**
     * Parse points change string like "+9", "-5", "" to integer
     */
    protected function parsePointsChange(?string $pointsDiff): int
    {
        if (empty($pointsDiff)) {
            return 0;
        }

        $cleaned = str_replace(' ', '', $pointsDiff);
        return (int) $cleaned;
    }

    /**
     * Sync a single player
     */
    protected function syncPlayer(ScrapedPlayer $player): void
    {
        // Find or create club
        $club = $this->findOrCreateClub($player->club_name);

        // Extract birth year from date_of_birth
        $birthYear = null;
        if (!empty($player->date_of_birth)) {
            if (preg_match('/(\d{4})/', $player->date_of_birth, $matches)) {
                $birthYear = (int) $matches[1];
            }
        }

        // Try to find existing user by name and birth year
        $user = $this->findUserByNameAndBirthYear(
            $player->first_name,
            $player->surname,
            $birthYear
        );

        if ($user) {
            // Update existing user
            $updateData = [
                'club_id' => $club?->id,
                'gender' => $this->mapGender($player->sex),
            ];

            // Set birth_year if not already set
            if ($birthYear && !$user->birth_year) {
                $updateData['birth_year'] = $birthYear;
            }

            $user->update($updateData);
            $this->markUserAsSynced($user);

            $player->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);

            $this->stats['updated']++;
        } else {
            // Don't create new users - skip if not found
            Log::info("Skipping player sync - user not found: {$player->first_name} {$player->surname}");
            $this->stats['skipped']++;
        }
    }

    /**
     * Sync a single ranking
     */
    protected function syncRanking(ScrapedRanking $ranking): void
    {
        // Parse name (usually "Surname, FirstName" or "FirstName Surname")
        $nameParts = $this->parseName($ranking->name);

        if (!$nameParts) {
            $ranking->update(['is_synced' => true]);
            $this->stats['skipped']++;
            return;
        }

        // Find or create club
        $club = $this->findOrCreateClub($ranking->club);

        // Parse birth year from the born field
        $birthYear = !empty($ranking->born) ? (int) $ranking->born : null;

        // Try to find existing user with birth year for better matching
        $user = $this->findUserByNameAndBirthYear(
            $nameParts['first_name'],
            $nameParts['last_name'],
            $birthYear
        );

        if ($user) {
            // Update user with ranking data
            $updateData = [
                'club_id' => $club?->id ?? $user->club_id,
                'gender' => $ranking->gender,
            ];

            // Set birth_year if not already set
            if ($birthYear && !$user->birth_year) {
                $updateData['birth_year'] = $birthYear;
            }

            $user->update($updateData);
            $this->markUserAsSynced($user);

            $ranking->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);

            $this->stats['updated']++;
        } else {
            // Create new user from ranking data
            $email = $this->generateEmail($nameParts['first_name'], $nameParts['last_name']);

            $user = User::create([
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'club_id' => $club?->id,
                'gender' => $ranking->gender,
                'birth_year' => $birthYear,
                'sbtf_player_id' => $ranking->profixio_player_id,
            ]);

            $this->markUserAsSynced($user);

            $ranking->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);

            $this->stats['created']++;
        }
    }

    /**
     * Find or create a club by name (with caching)
     */
    protected function findOrCreateClub(?string $clubName): ?Club
    {
        if (empty($clubName)) {
            return null;
        }

        // Normalize club name: trim and remove trailing asterisks
        $clubName = trim($clubName);
        $clubName = rtrim($clubName, '*');
        $clubName = trim($clubName); // Trim again after removing asterisk

        if (empty($clubName)) {
            return null;
        }

        // Generate slug for matching
        $slug = Str::slug($clubName);

        // Check cache first
        if (isset($this->clubsCache[$slug])) {
            // Return actual model from database, not from array
            return Club::find($this->clubsCache[$slug]['id']);
        }

        // Create new club if it doesn't exist
        $club = Club::create([
            'name' => $clubName,
            'slug' => $slug,
            'description' => null,
        ]);

        // Add to cache
        $this->clubsCache[$slug] = $club->toArray();

        return $club;
    }

    /**
     * Find user by first and last name
     */
    protected function findUserByName(string $firstName, string $lastName): ?User
    {
        return User::where('first_name', $firstName)
            ->where('last_name', $lastName)
            ->first();
    }

    /**
     * Find user by name and birth year for more accurate matching
     */
    protected function findUserByNameAndBirthYear(string $firstName, string $lastName, ?int $birthYear): ?User
    {
        $query = User::where('first_name', $firstName)
            ->where('last_name', $lastName);

        if ($birthYear) {
            // First try exact match with birth year
            $user = $query->clone()->where('birth_year', $birthYear)->first();
            if ($user) {
                return $user;
            }
        }

        // Fall back to name-only match
        return $query->first();
    }

    /**
     * Mark user as synced with SBTF data
     */
    protected function markUserAsSynced(User $user): void
    {
        $user->update([
            'sbtf_synced' => true,
            'sbtf_synced_at' => now(),
        ]);
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

        return [
            'first_name' => $name,
            'last_name' => '',
        ];
    }

    /**
     * Generate a unique email for a new user (with caching)
     */
    protected function generateEmail(string $firstName, string $lastName): string
    {
        $baseEmail = Str::slug($firstName . '.' . $lastName) . '@iracket.local';
        $email = $baseEmail;
        $counter = 1;

        // Check cache instead of database
        while (isset($this->existingEmails[$email])) {
            $email = Str::slug($firstName . '.' . $lastName . '.' . $counter) . '@iracket.local';
            $counter++;
        }

        // Add to cache so next check knows this email will be taken
        $this->existingEmails[$email] = true;

        return $email;
    }

    /**
     * Map Swedish sex/gender values to database format
     */
    protected function mapGender(?string $sex): ?string
    {
        if (empty($sex)) {
            return null;
        }

        $sex = trim($sex);

        // Handle both short and long Swedish formats
        return match (strtoupper($sex)) {
            'M', 'MAN', 'MALE' => 'male',
            'K', 'KVINNA', 'FEMALE' => 'female',
            default => null,
        };
    }

    /**
     * Reset stats
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Reset cache so it gets reinitialized on next sync
        $this->cacheInitialized = false;
        $this->clubsCache = [];
        $this->existingEmails = [];
    }

    /**
     * Get current stats
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Claim SBTF data for a registered user
     * This allows users who register later to claim their scraped data
     */
    public function claimDataForUser(User $user): array
    {
        $claimed = [
            'rankings' => 0,
            'players' => 0,
        ];

        // Find matching rankings by name and birth year
        $query = ScrapedRanking::whereNull('synced_user_id');

        // Parse user name to match rankings format (Surname, FirstName)
        $rankings = $query->get()->filter(function ($ranking) use ($user) {
            $nameParts = $this->parseName($ranking->name);
            if (!$nameParts) {
                return false;
            }

            // Check name match
            $nameMatch = strcasecmp($nameParts['first_name'], $user->first_name) === 0
                && strcasecmp($nameParts['last_name'], $user->last_name) === 0;

            if (!$nameMatch) {
                return false;
            }

            // Check birth year if available
            if ($user->birth_year && !empty($ranking->born)) {
                return (int) $ranking->born === $user->birth_year;
            }

            return true;
        });

        // Link rankings to user
        foreach ($rankings as $ranking) {
            $ranking->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);
            $claimed['rankings']++;

            // Update user with birth year if not set
            if (!$user->birth_year && !empty($ranking->born)) {
                $user->birth_year = (int) $ranking->born;
            }
        }

        // Find matching players
        $players = ScrapedPlayer::whereNull('synced_user_id')
            ->where('first_name', $user->first_name)
            ->where('surname', $user->last_name)
            ->get();

        // Filter by birth year if available
        if ($user->birth_year) {
            $players = $players->filter(function ($player) use ($user) {
                if (empty($player->date_of_birth)) {
                    return true;
                }
                if (preg_match('/(\d{4})/', $player->date_of_birth, $matches)) {
                    return (int) $matches[1] === $user->birth_year;
                }
                return true;
            });
        }

        // Link players to user
        foreach ($players as $player) {
            $player->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);
            $claimed['players']++;
        }

        // Mark user as synced
        if ($claimed['rankings'] > 0 || $claimed['players'] > 0) {
            $this->markUserAsSynced($user);
            $user->save();
        }

        Log::info("Claimed SBTF data for user {$user->name}", $claimed);

        return $claimed;
    }

    /**
     * Get unclaimed scraped data that might match a user
     * Useful for showing users potential matches they can claim
     */
    public function findPotentialMatches(User $user): array
    {
        $matches = [
            'rankings' => [],
            'players' => [],
        ];

        // Find potential ranking matches
        $rankings = ScrapedRanking::whereNull('synced_user_id')
            ->get()
            ->filter(function ($ranking) use ($user) {
                $nameParts = $this->parseName($ranking->name);
                if (!$nameParts) {
                    return false;
                }

                return strcasecmp($nameParts['first_name'], $user->first_name) === 0
                    && strcasecmp($nameParts['last_name'], $user->last_name) === 0;
            });

        $matches['rankings'] = $rankings->map(fn($r) => [
            'id' => $r->id,
            'period' => $r->period,
            'division' => $r->division,
            'position' => $r->position,
            'points' => $r->points,
            'born' => $r->born,
            'club' => $r->club,
        ])->values()->toArray();

        // Find potential player matches
        $players = ScrapedPlayer::whereNull('synced_user_id')
            ->where('first_name', $user->first_name)
            ->where('surname', $user->last_name)
            ->get();

        $matches['players'] = $players->map(fn($p) => [
            'id' => $p->id,
            'period' => $p->period,
            'club_name' => $p->club_name,
            'date_of_birth' => $p->date_of_birth,
            'license_type' => $p->license_type,
        ])->values()->toArray();

        return $matches;
    }
}
