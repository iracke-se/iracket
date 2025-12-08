<?php

namespace App\Livewire\Auth;

use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ConnectAccount extends Component
{
    public bool $isActivePlayer = true;
    public ?int $clubId = null;
    public ?int $playerId = null;
    public bool $acceptsPushNotifications = false;

    public $players = [];

    // Modal states
    public bool $showClubModal = false;
    public bool $showPlayerModal = false;
    public string $clubSearch = '';
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

        // Pre-fill with user's current club if set
        $this->clubId = $user->club_id;
        $this->isActivePlayer = $user->is_active_player ?? true;
        $this->acceptsPushNotifications = $user->accepts_push_notifications ?? false;

        // Set selected club name if club is set
        if ($this->clubId) {
            $club = Club::find($this->clubId);
            $this->selectedClubName = $club?->name;
            $this->loadPlayers();
        }
    }

    public function openClubModal()
    {
        $this->clubSearch = '';
        $this->showClubModal = true;
    }

    public function closeClubModal()
    {
        $this->showClubModal = false;
        $this->clubSearch = '';
    }

    public function selectClub($clubId)
    {
        $this->clubId = $clubId;
        $club = Club::find($clubId);
        $this->selectedClubName = $club?->name;
        $this->showClubModal = false;
        $this->clubSearch = '';

        // Reset player selection when club changes
        $this->playerId = null;
        $this->selectedPlayerName = null;
        $this->loadPlayers();
    }

    public function openPlayerModal()
    {
        if (!$this->clubId) {
            return;
        }
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
        $this->selectedPlayerName = $player ? $player->first_name . ' ' . $player->last_name : null;
        $this->showPlayerModal = false;
        $this->playerSearch = '';
    }

    public function updatedClubId($value)
    {
        $this->playerId = null;
        $this->players = [];

        if ($value) {
            $this->loadPlayers();
        }
    }

    public function loadPlayers()
    {
        if (!$this->clubId) {
            $this->players = [];
            return;
        }

        // Get players from the selected club that are synced from SBTF
        // and not already connected to another account
        $this->players = User::where('club_id', $this->clubId)
            ->where('sbtf_synced', true)
            ->where(function ($query) {
                $query->where('is_connected', false)
                    ->orWhereNull('is_connected');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    public function connect()
    {
        $user = Auth::user();

        // Validate if active player
        if ($this->isActivePlayer) {
            $this->validate([
                'clubId' => 'required|exists:clubs,id',
            ], [
                'clubId.required' => __('connect.club_required'),
                'clubId.exists' => __('connect.club_invalid'),
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
        // Get filtered clubs for modal
        $clubsQuery = Club::orderBy('name');
        if ($this->clubSearch) {
            $clubsQuery->where('name', 'like', '%' . $this->clubSearch . '%');
        }
        $clubs = $clubsQuery->get()->groupBy(function ($club) {
            return strtoupper(substr($club->name, 0, 1));
        });

        // Get filtered players for modal
        $playersGrouped = collect();
        if ($this->clubId) {
            $playersQuery = User::where('club_id', $this->clubId)
                ->where('sbtf_synced', true)
                ->where(function ($query) {
                    $query->where('is_connected', false)
                        ->orWhereNull('is_connected');
                })
                ->orderBy('first_name')
                ->orderBy('last_name');

            if ($this->playerSearch) {
                $playersQuery->where(function ($query) {
                    $query->where('first_name', 'like', '%' . $this->playerSearch . '%')
                        ->orWhere('last_name', 'like', '%' . $this->playerSearch . '%');
                });
            }

            $playersGrouped = $playersQuery->get()->groupBy(function ($player) {
                return strtoupper(substr($player->first_name, 0, 1));
            });
        }

        return view('livewire.auth.connect-account', [
            'clubsGrouped' => $clubs,
            'playersGrouped' => $playersGrouped,
        ]);
    }
}
