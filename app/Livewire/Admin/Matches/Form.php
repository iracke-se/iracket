<?php

namespace App\Livewire\Admin\Matches;

use App\Models\GameMatch;
use App\Models\User;
use Livewire\Component;

class Form extends Component
{
    public ?GameMatch $match = null;
    public ?int $player1_id = null;
    public ?int $player2_id = null;
    public ?string $played_at = null;
    public int $player1_sets = 0;
    public int $player2_sets = 0;
    public ?int $winner_id = null;
    public string $description = '';
    public string $status = 'pending';

    public function mount($id = null)
    {
        if ($id) {
            $this->match = GameMatch::findOrFail($id);
            $this->player1_id = $this->match->player1_id;
            $this->player2_id = $this->match->player2_id;
            $this->played_at = $this->match->played_at?->format('Y-m-d');
            $this->player1_sets = $this->match->player1_sets ?? 0;
            $this->player2_sets = $this->match->player2_sets ?? 0;
            $this->winner_id = $this->match->winner_id;
            $this->description = $this->match->description ?? '';
            $this->status = $this->match->status ?? 'pending';
        } else {
            $this->played_at = now()->format('Y-m-d');
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'player1_id' => 'required|exists:users,id',
            'player2_id' => 'required|exists:users,id|different:player1_id',
            'played_at' => 'required|date',
            'player1_sets' => 'required|integer|min:0',
            'player2_sets' => 'required|integer|min:0',
            'winner_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'status' => 'required|string|in:pending,confirmed,cancelled',
        ]);

        // Auto-determine winner based on sets
        if ($this->player1_sets > $this->player2_sets) {
            $validated['winner_id'] = $this->player1_id;
        } elseif ($this->player2_sets > $this->player1_sets) {
            $validated['winner_id'] = $this->player2_id;
        }

        if ($this->match) {
            $this->match->update($validated);
            session()->flash('message', 'Match updated successfully.');
        } else {
            $validated['created_by'] = auth()->id();
            GameMatch::create($validated);
            session()->flash('message', 'Match created successfully.');
        }

        return redirect()->route('admin.matches.index');
    }

    public function render()
    {
        $players = User::query()
            ->where('visible_in_players', true)
            ->orderBy('first_name')
            ->get();

        return view('livewire.admin.matches.form', [
            'players' => $players,
        ])->layout('components.layouts.admin');
    }
}
