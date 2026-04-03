<?php

namespace App\Livewire\Auth;

use App\Models\Club;
use App\Models\User;
use App\Traits\HasSearchableQueries;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ConnectAccount extends Component
{
    use HasSearchableQueries;
    public bool $isActivePlayer = true;
    public ?int $clubId = null;
    public ?int $playerId = null;
    public bool $acceptsPushNotifications = false;

    // Modal states
    public bool $showPlayerModal = false;
    public string $playerSearch = '';

    // Selected names for display
    public ?string $selectedClubName = null;
    public ?string $selectedPlayerName = null;

    public function mount()
    {
        $user = Auth::user();

        // If already fully connected as an active player, redirect to players
        if ($user->is_connected && $user->is_active_player) {
            return redirect()->route('players.index');
        }

        $this->isActivePlayer = $user->is_active_player ?? true;
        $this->acceptsPushNotifications = $user->accepts_push_notifications ?? false;
    }

    public function openPlayerModal()
    {
        $this->playerSearch = '';
        $this->showPlayerModal = true;
    }

    public function closePlayerModal()
    {
        $this->showPlayerModal = false;
        $this->playerSearch = '';
    }

    public function selectPlayer($playerId)
    {
        $this->playerId = $playerId;
        $player = User::find($playerId);

        if ($player) {
            $this->selectedPlayerName = $player->first_name . ' ' . $player->last_name;

            // Auto-select club from player's profile
            $this->clubId = $player->club_id;
            if ($this->clubId) {
                $club = Club::find($this->clubId);
                $this->selectedClubName = $club?->name;
            }
        }

        $this->showPlayerModal = false;
        $this->playerSearch = '';
    }


    public function continueAsGuest()
    {
        $user = Auth::user();

        // Mark as connected without linking to SBTF player
        $user->update([
            'is_connected' => true,
            'is_active_player' => false,
            'club_id' => null,
            'accepts_push_notifications' => $this->acceptsPushNotifications,
        ]);

        return redirect()->route('players.index');
    }

    public function connect()
    {
        $user = Auth::user();

        // Validate if active player and no player selected
        if ($this->isActivePlayer && !$this->playerId) {
            $this->validate([
                'playerId' => 'required',
            ], [
                'playerId.required' => __('connect.player_required'),
            ]);
        }

        // If player selected, merge data from that player
        if ($this->playerId && $this->playerId !== $user->id) {
            $existingPlayer = User::find($this->playerId);

            if ($existingPlayer && $existingPlayer->sbtf_synced) {
                // Validate that SBTF player has required data
                if (empty($existingPlayer->first_name) || empty($existingPlayer->last_name)) {
                    Log::error('SBTF player has invalid name data', [
                        'player_id' => $existingPlayer->id,
                        'sbtf_player_id' => $existingPlayer->sbtf_player_id,
                    ]);

                    $this->addError('playerId', __('connect.invalid_sbtf_player_data'));
                    return;
                }

                // Preserve the user's registered name before transferring SBTF data
                if (!$user->user_fullname) {
                    $currentName = trim($user->first_name . ' ' . $user->last_name);
                    if (!empty($currentName)) {
                        $user->user_fullname = $currentName;
                    }
                }

                // Transfer SBTF identifiers
                $user->sbtf_player_id = $existingPlayer->sbtf_player_id;
                $user->sbtf_synced = true;
                $user->sbtf_synced_at = $existingPlayer->sbtf_synced_at;

                // Transfer official SBTF player profile data (SBTF data is authoritative)
                $user->first_name = $existingPlayer->first_name;
                $user->last_name = $existingPlayer->last_name;

                // Transfer optional fields if available from SBTF player
                if (!empty($existingPlayer->gender)) {
                    $user->gender = $existingPlayer->gender;
                }

                if (!empty($existingPlayer->birth_year)) {
                    $user->birth_year = $existingPlayer->birth_year;
                }

                // Save all changes before transferring relationships
                $user->save();

                // Transfer rankings if any
                $existingPlayer->monthlyRankings()->update(['user_id' => $user->id]);

                // Transfer matches if any
                $existingPlayer->matchesAsPlayer1()->update(['player1_id' => $user->id]);
                $existingPlayer->matchesAsPlayer2()->update(['player2_id' => $user->id]);

                // Delete the old player record
                $existingPlayer->delete();
            }
        }

        // Update user with connection data
        $user->update([
            'is_connected' => true,
            'is_active_player' => $this->isActivePlayer,
            'club_id' => $this->isActivePlayer ? $this->clubId : null,
            'accepts_push_notifications' => $this->acceptsPushNotifications,
        ]);

        return redirect()->route('players.index');
    }

    public function render()
    {
        $playersGrouped = collect();
        $search = trim($this->playerSearch);

        // Only search if we have at least 2 characters to prevent loading too many records
        if (strlen($search) >= 2) {
            // Get filtered players for modal
            $playersQuery = User::where('sbtf_synced', true)
                ->where(function ($query) {
                    $query->where('is_connected', false)
                        ->orWhereNull('is_connected');
                })
                ->orderBy('first_name')
                ->orderBy('last_name');

            // If search contains space, split and search first/last name parts
            if (str_contains($search, ' ')) {
                $parts = explode(' ', $search, 2);
                $firstName = trim($parts[0]);
                $lastName = trim($parts[1] ?? '');

                if ($firstName && $lastName) {
                    $this->applySearch($playersQuery, $firstName, ['first_name']);
                    $this->applySearch($playersQuery, $lastName, ['last_name']);
                } else {
                    $this->applySearch($playersQuery, $firstName ?: $lastName, ['first_name', 'last_name']);
                }
            } else {
                $this->applySearch($playersQuery, $search, ['first_name', 'last_name']);
            }

            // Limit to 50 results to ensure performance
            $playersGrouped = $playersQuery->limit(50)->get()->groupBy(function ($player) {
                return strtoupper(substr($player->first_name, 0, 1));
            });
        }

        return view('livewire.auth.connect-account', [
            'playersGrouped' => $playersGrouped,
        ]);
    }
}
