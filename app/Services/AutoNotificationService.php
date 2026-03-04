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
     * Temporarily set app locale to the user's preferred locale,
     * execute the callback, then restore the previous locale.
     */
    private function withLocale(User $user, callable $callback): mixed
    {
        $previous = app()->getLocale();
        app()->setLocale($user->locale ?? config('app.locale'));
        try {
            return $callback();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * Send notification when a new match is created
     */
    public function matchCreated(GameMatch $match, User $creator): void
    {
        $opponent = $match->player1_id === $creator->id
            ? User::find($match->player2_id)
            : User::find($match->player1_id);

        if (!$opponent) {
            return;
        }

        $score = $match->player1_id === $creator->id
            ? "{$match->player1_sets}-{$match->player2_sets}"
            : "{$match->player2_sets}-{$match->player1_sets}";

        [$title, $message] = $this->withLocale($opponent, fn() => [
            __('user-notifications.match_created_title'),
            __('user-notifications.match_created_message', ['name' => $creator->name, 'score' => $score]),
        ]);

        Notification::create([
            'user_id' => $opponent->id,
            'type'    => self::TYPE_MATCH_CREATED,
            'title'   => $title,
            'message' => $message,
            'data'    => ['match_id' => $match->id, 'url' => route('matches.show', $match)],
            'icon'    => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification when a match is confirmed
     */
    public function matchConfirmed(GameMatch $match, User $confirmer): void
    {
        $creator = User::find($match->created_by);

        if (!$creator || $creator->id === $confirmer->id) {
            return;
        }

        [$title, $message] = $this->withLocale($creator, fn() => [
            __('user-notifications.match_confirmed_title'),
            __('user-notifications.match_confirmed_message', ['name' => $confirmer->name]),
        ]);

        Notification::create([
            'user_id' => $creator->id,
            'type'    => self::TYPE_MATCH_CONFIRMED,
            'title'   => $title,
            'message' => $message,
            'data'    => ['match_id' => $match->id, 'url' => route('matches.show', $match)],
            'icon'    => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification when a match is rejected
     */
    public function matchRejected(GameMatch $match, User $rejecter): void
    {
        $creator = User::find($match->created_by);

        if (!$creator || $creator->id === $rejecter->id) {
            return;
        }

        [$title, $message] = $this->withLocale($creator, fn() => [
            __('user-notifications.match_rejected_title'),
            __('user-notifications.match_rejected_message', ['name' => $rejecter->name]),
        ]);

        Notification::create([
            'user_id' => $creator->id,
            'type'    => self::TYPE_MATCH_REJECTED,
            'title'   => $title,
            'message' => $message,
            'data'    => ['match_id' => $match->id, 'url' => route('matches.index')],
            'icon'    => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification for ranking update
     */
    public function rankingUpdate(User $user, int $oldRank, int $newRank, int $pointsChange): void
    {
        $direction = $newRank < $oldRank ? 'up' : 'down';
        $points    = ($pointsChange > 0 ? '+' : '') . $pointsChange;

        [$title, $message] = $this->withLocale($user, function () use ($direction, $oldRank, $newRank, $points) {
            return $direction === 'up'
                ? [
                    __('user-notifications.ranking_improved_title'),
                    __('user-notifications.ranking_improved_message', ['old' => $oldRank, 'new' => $newRank, 'points' => $points]),
                ]
                : [
                    __('user-notifications.ranking_changed_title'),
                    __('user-notifications.ranking_changed_message', ['old' => $oldRank, 'new' => $newRank, 'points' => $points]),
                ];
        });

        Notification::create([
            'user_id' => $user->id,
            'type'    => self::TYPE_RANKING_UPDATE,
            'title'   => $title,
            'message' => $message,
            'data'    => ['old_rank' => $oldRank, 'new_rank' => $newRank, 'points_change' => $pointsChange, 'url' => route('bubbler.index')],
            'icon'    => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification for achievement unlocked
     */
    public function achievementUnlocked(User $user, string $achievementName, string $description): void
    {
        [$title, $message] = $this->withLocale($user, fn() => [
            __('user-notifications.achievement_title'),
            __('user-notifications.achievement_message', ['achievement' => $achievementName, 'description' => $description]),
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type'    => self::TYPE_ACHIEVEMENT,
            'title'   => $title,
            'message' => $message,
            'data'    => ['achievement' => $achievementName],
            'icon'    => 'assets/images/icon.png',
        ]);
    }

    /**
     * Send notification when someone follows a user
     */
    public function newFollower(User $user, User $follower): void
    {
        [$title, $message] = $this->withLocale($user, fn() => [
            __('user-notifications.follow_title'),
            __('user-notifications.follow_message', ['name' => $follower->name]),
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type'    => self::TYPE_FOLLOW,
            'title'   => $title,
            'message' => $message,
            'data'    => ['follower_id' => $follower->id, 'url' => route('players.show', $follower)],
            'icon'    => 'assets/images/icon.png',
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
