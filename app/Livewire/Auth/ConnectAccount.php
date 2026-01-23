<?php

namespace App\Livewire\Auth;

use App\Models\Club;
use App\Models\User;
use App\Traits\HasSearchableQueries;
use Illuminate\Support\Facades\Auth;
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

    public $players = [];

    // Modal states
    public bool $showPlayerModal = false;
    public string $playerSearch = '';

    // Selected names for display
    public ?string $selectedClubName = null;
    public ?string $selectedPlayerName = null;

    public function mount()
    {
        $user = Auth::user();

        // If already connected, redirect to players
        if ($user->is_connected) {
            return redirect()->route('players.index');
        }

        $this->isActivePlayer = $user->is_active_player ?? true;
        $this->acceptsPushNotifications = $user->accepts_push_notifications ?? false;

        // Load all available players
        $this->loadPlayers();
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

    public function loadPlayers()
    {
        // Get all players that are synced from SBTF
        // and not already connected to another account
        $this->players = User::where('sbtf_synced', true)
            ->where(function ($query) {
                $query->where('is_connected', false)
                    ->orWhereNull('is_connected');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'club_id']);
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
                // Transfer relevant data from the existing SBTF player to current user
                $user->sbtf_player_id = $existingPlayer->sbtf_player_id;
                $user->sbtf_synced = true;
                $user->sbtf_synced_at = $existingPlayer->sbtf_synced_at;

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
        // Get filtered players for modal
        $playersQuery = User::where('sbtf_synced', true)
            ->where(function ($query) {
                $query->where('is_connected', false)
                    ->orWhereNull('is_connected');
            })
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($this->playerSearch) {
            $this->applySearch($playersQuery, $this->playerSearch, ['first_name', 'last_name']);
        }

        $playersGrouped = $playersQuery->get()->groupBy(function ($player) {
            return strtoupper(substr($player->first_name, 0, 1));
        });

        return view('livewire.auth.connect-account', [
            'playersGrouped' => $playersGrouped,
        ]);
    }
}
