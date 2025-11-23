<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\GameMatch;

class AutoNotificationService
{
    /**
     * Notification types
     */
    public const TYPE_MATCH_CREATED = 'match_created';
    public const TYPE_MATCH_CONFIRMED = 'match_confirmed';
    public const TYPE_MATCH_REJECTED = 'match_rejected';
    public const TYPE_RANKING_UPDATE = 'ranking_update';
    public const TYPE_ACHIEVEMENT = 'achievement';
    public const TYPE_BUBBLER = 'bubbler';
    public const TYPE_FOLLOW = 'follow';

    /**
     * Send notification when a new match is created
     */
    public function matchCreated(GameMatch $match, User $creator): void
    {
        // Notify the opponent
        $opponent = $match->player1_id === $creator->id
            ? User::find($match->player2_id)
            : User::find($match->player1_id);

        if (!$opponent) {
            return;
        }

        $creatorName = $creator->name;
        $score = $match->player1_id === $creator->id
            ? "{$match->player1_sets}-{$match->player2_sets}"
            : "{$match->player2_sets}-{$match->player1_sets}";

        Notification::create([
            'user_id' => $opponent->id,
            'type' => self::TYPE_MATCH_CREATED,
            'title' => __('New Match Added'),
            'message' => __(':name added a match with you (:score). Please confirm or reject it.', [
                'name' => $creatorName,
                'score' => $score,
            ]),
            'data' => [
                'match_id' => $match->id,
                'url' => route('matches.show', $match),
            ],
            'icon' => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification when a match is confirmed
     */
    public function matchConfirmed(GameMatch $match, User $confirmer): void
    {
        // Notify the creator
        $creator = User::find($match->created_by);

        if (!$creator || $creator->id === $confirmer->id) {
            return;
        }

        $confirmerName = $confirmer->name;

        Notification::create([
            'user_id' => $creator->id,
            'type' => self::TYPE_MATCH_CONFIRMED,
            'title' => __('Match Confirmed'),
            'message' => __(':name confirmed your match.', [
                'name' => $confirmerName,
            ]),
            'data' => [
                'match_id' => $match->id,
                'url' => route('matches.show', $match),
            ],
            'icon' => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification when a match is rejected
     */
    public function matchRejected(GameMatch $match, User $rejecter): void
    {
        // Notify the creator
        $creator = User::find($match->created_by);

        if (!$creator || $creator->id === $rejecter->id) {
            return;
        }

        $rejecterName = $rejecter->name;

        Notification::create([
            'user_id' => $creator->id,
            'type' => self::TYPE_MATCH_REJECTED,
            'title' => __('Match Rejected'),
            'message' => __(':name rejected your match.', [
                'name' => $rejecterName,
            ]),
            'data' => [
                'match_id' => $match->id,
                'url' => route('matches.index'),
            ],
            'icon' => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification for ranking update
     */
    public function rankingUpdate(User $user, int $oldRank, int $newRank, int $pointsChange): void
    {
        $direction = $newRank < $oldRank ? 'up' : 'down';
        $title = $direction === 'up' ? __('Ranking Improved!') : __('Ranking Changed');

        $message = $direction === 'up'
            ? __('You moved up from #:old to #:new! (:points points)', [
                'old' => $oldRank,
                'new' => $newRank,
                'points' => ($pointsChange > 0 ? '+' : '') . $pointsChange,
            ])
            : __('You moved from #:old to #:new (:points points)', [
                'old' => $oldRank,
                'new' => $newRank,
                'points' => ($pointsChange > 0 ? '+' : '') . $pointsChange,
            ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => self::TYPE_RANKING_UPDATE,
            'title' => $title,
            'message' => $message,
            'data' => [
                'old_rank' => $oldRank,
                'new_rank' => $newRank,
                'points_change' => $pointsChange,
                'url' => route('bubbler.index'),
            ],
            'icon' => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification for achievement unlocked
     */
    public function achievementUnlocked(User $user, string $achievementName, string $description): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => self::TYPE_ACHIEVEMENT,
            'title' => __('Achievement Unlocked!'),
            'message' => __(':achievement - :description', [
                'achievement' => $achievementName,
                'description' => $description,
            ]),
            'data' => [
                'achievement' => $achievementName,
            ],
            'icon' => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification when someone follows a user
     */
    public function newFollower(User $user, User $follower): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => self::TYPE_FOLLOW,
            'title' => __('New Follower'),
            'message' => __(':name started following you.', [
                'name' => $follower->name,
            ]),
            'data' => [
                'follower_id' => $follower->id,
                'url' => route('players.show', $follower),
            ],
            'icon' => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send a custom notification
     */
    public function custom(User $user, string $type, string $title, string $message, array $data = [], ?string $icon = null): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => !empty($data) ? $data : null,
            'icon' => $icon ?? 'assets/images/icon.png',
        ]);
    }
}
