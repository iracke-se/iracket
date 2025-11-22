<?php

namespace App\Livewire\User\Matches;

use App\Models\GameMatch;
use App\Models\User;
use Livewire\Component;

class Index extends Component
{
    public int $selectedYear;
    public ?int $selectedOpponent = null;
    public string $opponentSearch = '';

    public function mount()
    {
        $this->selectedYear = now()->year;
    }

    public function selectOpponent(?int $opponentId): void
    {
        $this->selectedOpponent = $opponentId;
        $this->opponentSearch = '';
    }

    public function clearOpponentFilter(): void
    {
        $this->selectedOpponent = null;
        $this->opponentSearch = '';
    }

    public function render()
    {
        $user = auth()->user();

        $matchesQuery = GameMatch::where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)
                  ->orWhere('player2_id', $user->id);
        })
        ->whereYear('played_at', $this->selectedYear)
        ->with(['player1.club', 'player2.club']);

        // Filter by opponent
        if ($this->selectedOpponent) {
            $matchesQuery->where(function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('player1_id', $user->id)
                      ->where('player2_id', $this->selectedOpponent);
                })->orWhere(function ($q) use ($user) {
                    $q->where('player2_id', $user->id)
                      ->where('player1_id', $this->selectedOpponent);
                });
            });
        }

        $matches = $matchesQuery->orderBy('played_at', 'desc')
            ->get()
            ->groupBy(function ($match) {
                return $match->played_at->format('F Y');
            });

        // Get available years
        $years = GameMatch::where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)
                  ->orWhere('player2_id', $user->id);
        })
        ->selectRaw('YEAR(played_at) as year')
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year')
        ->toArray();

        if (empty($years)) {
            $years = [now()->year];
        }

        // Get opponents for filter dropdown
        $opponentIds = GameMatch::where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)
                  ->orWhere('player2_id', $user->id);
        })
        ->get()
        ->flatMap(function ($match) use ($user) {
            return $match->player1_id === $user->id
                ? [$match->player2_id]
                : [$match->player1_id];
        })
        ->unique()
        ->values();

        $opponentsQuery = User::whereIn('id', $opponentIds)->with('club');

        if ($this->opponentSearch) {
            $opponentsQuery->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->opponentSearch . '%')
                  ->orWhere('last_name', 'like', '%' . $this->opponentSearch . '%');
            });
        }

        $opponents = $opponentsQuery->orderBy('first_name')->get();

        // Get selected opponent details
        $selectedOpponentUser = $this->selectedOpponent
            ? User::find($this->selectedOpponent)
            : null;

        return view('livewire.user.matches.index', [
            'matchesByMonth' => $matches,
            'years' => $years,
            'opponents' => $opponents,
            'selectedOpponentUser' => $selectedOpponentUser,
        ])->layout('components.layouts.app');
    }
}
