<?php

namespace App\Livewire\User\Matches;

use App\Models\GameMatch;
use App\Models\MonthlyRanking;
use App\Models\User;
use App\Services\AutoNotificationService;
use App\Services\PointsCalculationService;
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

    // Keys map to lang/user-matches comment_* translation keys; values are stored in DB
    public array $availableComments = [
        'good_backhand'       => 'Good backhand',
        'good_forehand'       => 'Good forehand',
        'strong_serve'        => 'Strong serve',
        'fast_footwork'       => 'Fast footwork',
        'excellent_net_play'  => 'Excellent net play',
        'super_sensitive'     => 'Super sensitive',
        'great_sportsmanship' => 'Great sportsmanship',
        'consistent_player'   => 'Consistent player',
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

            abort_if($match->created_by !== $user->id, 403);

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
            $this->addError('opponent_id', __('user-matches.cannot_play_yourself'));
            return;
        }

        // Validate there's a winner (no ties)
        if ($this->my_sets === $this->opponent_sets) {
            $this->addError('my_sets', __('user-matches.cannot_tie'));
            return;
        }

        $user = auth()->user();
        $winnerId = $this->my_sets > $this->opponent_sets ? $user->id : $this->opponent_id;

        // Get current month/year for ranking
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get official scraped points for both players
        $player1Ranking = MonthlyRanking::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->first();

        $player2Ranking = MonthlyRanking::where('user_id', $this->opponent_id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->first();

        // Add any previous manual match points from this month (not stored in monthly_rankings)
        $excludeMatchId = $this->match?->id;

        $player1ManualDelta = GameMatch::where(function ($q) use ($user) {
                $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id);
            })
            ->where('is_manual', true)
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->when($excludeMatchId, fn($q) => $q->where('id', '!=', $excludeMatchId))
            ->get()
            ->sum(fn($m) => $m->player1_id === $user->id ? $m->player1_points_change : $m->player2_points_change);

        $player2ManualDelta = GameMatch::where(function ($q) {
                $q->where('player1_id', $this->opponent_id)->orWhere('player2_id', $this->opponent_id);
            })
            ->where('is_manual', true)
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->when($excludeMatchId, fn($q) => $q->where('id', '!=', $excludeMatchId))
            ->get()
            ->sum(fn($m) => $m->player1_id === $this->opponent_id ? $m->player1_points_change : $m->player2_points_change);

        $player1Points = ($player1Ranking?->points ?? 0) + $player1ManualDelta;
        $player2Points = ($player2Ranking?->points ?? 0) + $player2ManualDelta;

        // Calculate points changes
        $pointsService = app(PointsCalculationService::class);
        $pointsResult = $pointsService->calculateMatchPoints(
            $player1Points,
            $player2Points,
            $winnerId,
            $user->id,
            $this->opponent_id
        );

        $data = [
            'player1_id' => $user->id,
            'player2_id' => $this->opponent_id,
            'played_at' => $this->played_at,
            'player1_sets' => $this->my_sets,
            'player2_sets' => $this->opponent_sets,
            'player1_points_before' => $player1Points,
            'player2_points_before' => $player2Points,
            'player1_points_change' => $pointsResult['player1_change'],
            'player2_points_change' => $pointsResult['player2_change'],
            'winner_id' => $winnerId,
            'player1_comments' => [],
            'player2_comments' => $this->opponent_comments,
            'description' => $this->description ?: null,
            'status' => 'pending',
            'is_manual' => true,
            'created_by' => $user->id,
        ];

        if ($this->match) {
            $this->match->update($data);
            session()->flash('message', __('user-matches.updated_successfully'));
        } else {
            $match = GameMatch::create($data);

            // Send notification to opponent
            $notificationService = app(AutoNotificationService::class);
            $notificationService->matchCreated($match, $user);

            session()->flash('message', __('user-matches.created_successfully'));
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
