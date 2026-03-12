<?php

namespace App\Services\Scraper;

use App\Models\District;
use App\Models\Scraper\ScrapedDistrictPlayer;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DistrictSyncService
{
    protected array $stats = [
        'matched'   => 0,
        'unmatched' => 0,
        'errors'    => 0,
    ];

    /** @var array<int, int> profixio_district_id → districts.id */
    protected array $districtCache = [];

    /**
     * Sync scraped district players to the users table.
     *
     * For each unsynced scraped_district_players record:
     *  1. Try matching user by sbtf_player_id (profixio_player_id)
     *  2. Fall back to (last_name, first_name, birth_year) — uses the existing DB index
     *  3. If matched: update users.district_id
     *  4. Mark record as synced regardless (even if no user found yet)
     */
    public function sync(?int $runId = null): array
    {
        $this->stats        = ['matched' => 0, 'unmatched' => 0, 'errors' => 0];
        $this->districtCache = District::pluck('id', 'profixio_id')->toArray();

        $query = ScrapedDistrictPlayer::where('is_synced', false);

        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $query->chunkById(100, function ($records) {
            foreach ($records as $record) {
                try {
                    $this->syncRecord($record);
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error(
                        "DistrictSync error for {$record->surname}, {$record->first_name}: " .
                        $e->getMessage()
                    );
                }
            }
        });

        return $this->stats;
    }

    protected function syncRecord(ScrapedDistrictPlayer $record): void
    {
        $districtId = $this->districtCache[$record->profixio_district_id] ?? null;

        // Try matching by profixio_player_id first (most reliable)
        $user = null;
        if ($record->profixio_player_id) {
            $user = User::where('sbtf_player_id', $record->profixio_player_id)->first();
        }

        // Fall back to name + birth year (uses users_player_match_index)
        if (!$user && $record->birth_year) {
            $user = User::where('last_name', $record->surname)
                ->where('first_name', $record->first_name)
                ->where('birth_year', (int) $record->birth_year)
                ->first();
        }

        if ($user && $districtId) {
            $user->update(['district_id' => $districtId]);
            $record->update(['is_synced' => true, 'synced_user_id' => $user->id]);
            $this->stats['matched']++;
        } else {
            // No matching user yet (player not registered) — mark synced so we don't
            // re-process on every sync. Will be re-synced on next scraper run anyway
            // since upsert resets is_synced = false.
            $record->update(['is_synced' => true]);
            $this->stats['unmatched']++;
        }
    }
}
