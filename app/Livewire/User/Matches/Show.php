<?php

namespace App\Livewire\User\Matches;

use App\Models\GameMatch;
use App\Services\AutoNotificationService;
use Livewire\Component;

class Show extends Component
{
    public GameMatch $match;

    public function mount(GameMatch $match)
    {
        $this->match = $match->loadMissing(['liveMatchGame.sets', 'liveMatchGame.detail']);
    }

    public function confirmMatch(): void
    {
        $user = auth()->user();

        abort_if(!$this->match->isPlayer($user), 403);
        abort_if($this->match->created_by === $user->id, 403);
        abort_if($this->match->status !== 'pending', 403);

        $this->match->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        app(AutoNotificationService::class)->matchConfirmed($this->match, $user);

        session()->flash('message', __('user-matches.confirmed_successfully'));
        $this->redirect(route('matches.show', $this->match), navigate: true);
    }

    public function rejectMatch(): void
    {
        $user = auth()->user();

        abort_if(!$this->match->isPlayer($user), 403);
        abort_if($this->match->created_by === $user->id, 403);
        abort_if($this->match->status !== 'pending', 403);

        $this->match->update(['status' => 'disputed']);

        app(AutoNotificationService::class)->matchRejected($this->match, $user);

        session()->flash('message', __('user-matches.rejected_successfully'));
        $this->redirect(route('matches.index'), navigate: true);
    }

    public function deleteMatch(): void
    {
        $user = auth()->user();

        abort_if(!$this->match->isPlayer($user), 403);

        $this->match->delete();

        $this->redirect(route('matches.index'), navigate: true);
    }

    public function render()
    {
        // Get other matches between these two players
        $otherMatches = GameMatch::query()
            ->where('id', '!=', $this->match->id)
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('player1_id', $this->match->player1_id)
                        ->where('player2_id', $this->match->player2_id);
                })->orWhere(function ($subQ) {
                    $subQ->where('player1_id', $this->match->player2_id)
                        ->where('player2_id', $this->match->player1_id);
                });
            })
            ->with(['player1', 'player2', 'winner', 'scrapedMatches'])
            ->orderBy('played_at', 'desc')
            ->limit(10)
            ->get();

        return view('livewire.user.matches.show', [
            'otherMatches' => $otherMatches,
        ])->layout('components.layouts.app');
    }
}
