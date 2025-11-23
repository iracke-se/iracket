<?php

namespace App\Livewire\User\Matches;

use App\Models\GameMatch;
use App\Models\User;
use App\Services\AutoNotificationService;
use Livewire\Component;

class Form extends Component
{
    public ?GameMatch $match = null;

    public string $played_at = '';
    public ?int $opponent_id = null;
    public int $my_sets = 0;
    public int $opponent_sets = 0;
    public array $opponent_comments = [];
    public string $custom_comment = '';
    public string $description = '';

    public string $opponentSearch = '';

    public array $availableComments = [
        'Good backhand',
        'Good forehand',
        'Strong serve',
        'Fast footwork',
        'Excellent net play',
        'Super sensitive',
        'Great sportsmanship',
        'Consistent player',
    ];

    protected $rules = [
        'played_at' => 'required|date|before_or_equal:today',
        'opponent_id' => 'required|exists:users,id',
        'my_sets' => 'required|integer|min:0|max:5',
        'opponent_sets' => 'required|integer|min:0|max:5',
        'opponent_comments' => 'array',
        'description' => 'nullable|string|max:1000',
    ];

    public function mount(?GameMatch $match = null)
    {
        if ($match && $match->exists) {
            $this->match = $match;
            $user = auth()->user();

            $this->played_at = $match->played_at->format('Y-m-d');

            // Determine which player is the current user
            if ($match->player1_id === $user->id) {
                $this->opponent_id = $match->player2_id;
                $this->my_sets = $match->player1_sets;
                $this->opponent_sets = $match->player2_sets;
                $this->opponent_comments = $match->player2_comments ?? [];
            } else {
                $this->opponent_id = $match->player1_id;
                $this->my_sets = $match->player2_sets;
                $this->opponent_sets = $match->player1_sets;
                $this->opponent_comments = $match->player1_comments ?? [];
            }

            $this->description = $match->description ?? '';
        } else {
            $this->played_at = now()->format('Y-m-d');
        }
    }

    public function toggleComment(string $comment)
    {
        if (in_array($comment, $this->opponent_comments)) {
            $this->opponent_comments = array_values(array_filter($this->opponent_comments, fn($c) => $c !== $comment));
        } else {
            $this->opponent_comments[] = $comment;
        }
    }

    public function addCustomComment()
    {
        if ($this->custom_comment && !in_array($this->custom_comment, $this->opponent_comments)) {
            $this->opponent_comments[] = $this->custom_comment;
            $this->custom_comment = '';
        }
    }

    public function save()
    {
        $this->validate();

        // Validate not playing against self
        if ($this->opponent_id === auth()->id()) {
            $this->addError('opponent_id', __('You cannot play against yourself.'));
            return;
        }

        // Validate there's a winner (no ties)
        if ($this->my_sets === $this->opponent_sets) {
            $this->addError('my_sets', __('Match cannot end in a tie.'));
            return;
        }

        $user = auth()->user();
        $winnerId = $this->my_sets > $this->opponent_sets ? $user->id : $this->opponent_id;

        $data = [
            'player1_id' => $user->id,
            'player2_id' => $this->opponent_id,
            'played_at' => $this->played_at,
            'player1_sets' => $this->my_sets,
            'player2_sets' => $this->opponent_sets,
            'winner_id' => $winnerId,
            'player1_comments' => [],
            'player2_comments' => $this->opponent_comments,
            'description' => $this->description ?: null,
            'status' => 'pending',
            'created_by' => $user->id,
        ];

        if ($this->match) {
            $this->match->update($data);
            session()->flash('message', __('Match updated successfully.'));
        } else {
            $match = GameMatch::create($data);

            // Send notification to opponent
            $notificationService = app(AutoNotificationService::class);
            $notificationService->matchCreated($match, $user);

            session()->flash('message', __('Match created successfully.'));
        }

        return redirect()->route('matches.index');
    }

    public function render()
    {
        $opponents = User::where('id', '!=', auth()->id())
            ->when($this->opponentSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->opponentSearch . '%')
                      ->orWhere('last_name', 'like', '%' . $this->opponentSearch . '%');
                });
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return view('livewire.user.matches.form', [
            'opponents' => $opponents,
        ])->layout('components.layouts.app');
    }
}
