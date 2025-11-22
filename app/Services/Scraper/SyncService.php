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

    /**
     * Sync players from scraped data to users table
     */
    public function syncPlayers(?int $runId = null): array
    {
        $this->resetStats();

        $query = ScrapedPlayer::where('is_synced', false);

        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $players = $query->get();

        Log::info("Syncing {$players->count()} players");

        foreach ($players as $player) {
            try {
                $this->syncPlayer($player);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error("Failed to sync player: {$player->full_name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->stats;
    }

    /**
     * Sync rankings from scraped data to users table
     */
    public function syncRankings(?int $runId = null): array
    {
        $this->resetStats();

        $query = ScrapedRanking::where('is_synced', false);

        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $rankings = $query->get();

        Log::info("Syncing {$rankings->count()} rankings");

        foreach ($rankings as $ranking) {
            try {
                $this->syncRanking($ranking);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error("Failed to sync ranking: {$ranking->name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->stats;
    }

    /**
     * Sync a single player
     */
    protected function syncPlayer(ScrapedPlayer $player): void
    {
        // Find or create club
        $club = $this->findOrCreateClub($player->club_name);

        // Try to find existing user by name
        $user = $this->findUserByName($player->first_name, $player->surname);

        if ($user) {
            // Update existing user
            $user->update([
                'club_id' => $club?->id,
                'gender' => $player->gender,
            ]);

            $player->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);

            $this->stats['updated']++;
        } else {
            // Create new user
            $user = User::create([
                'first_name' => $player->first_name,
                'last_name' => $player->surname,
                'email' => $this->generateEmail($player->first_name, $player->surname),
                'password' => Hash::make(Str::random(16)),
                'club_id' => $club?->id,
                'gender' => $player->gender,
                'visible_in_players' => true,
            ]);

            $player->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);

            $this->stats['created']++;
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

        // Try to find existing user
        $user = $this->findUserByName($nameParts['first_name'], $nameParts['last_name']);

        if ($user) {
            // Update user with ranking data
            $user->update([
                'club_id' => $club?->id ?? $user->club_id,
                'ranking_points' => $ranking->points,
                'gender' => $ranking->gender,
            ]);

            $ranking->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);

            $this->stats['updated']++;
        } else {
            // Create new user
            $user = User::create([
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'email' => $this->generateEmail($nameParts['first_name'], $nameParts['last_name']),
                'password' => Hash::make(Str::random(16)),
                'club_id' => $club?->id,
                'ranking_points' => $ranking->points,
                'gender' => $ranking->gender,
                'visible_in_players' => true,
            ]);

            $ranking->update([
                'is_synced' => true,
                'synced_user_id' => $user->id,
            ]);

            $this->stats['created']++;
        }
    }

    /**
     * Find or create a club by name
     */
    protected function findOrCreateClub(?string $clubName): ?Club
    {
        if (empty($clubName)) {
            return null;
        }

        $clubName = trim($clubName);

        return Club::firstOrCreate(
            ['name' => $clubName],
            [
                'slug' => Str::slug($clubName),
                'description' => null,
            ]
        );
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
     * Generate a unique email for a new user
     */
    protected function generateEmail(string $firstName, string $lastName): string
    {
        $baseEmail = Str::slug($firstName . '.' . $lastName) . '@iracket.local';
        $email = $baseEmail;
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = Str::slug($firstName . '.' . $lastName . '.' . $counter) . '@iracket.local';
            $counter++;
        }

        return $email;
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
    }

    /**
     * Get current stats
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
