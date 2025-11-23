<?php

namespace App\Services\Firebase;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send notification to a single user
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->fcm_token) {
            Log::warning("User {$user->id} has no FCM token");
            return false;
        }

        return $this->sendToToken($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(Collection $users, string $title, string $body, array $data = []): array
    {
        $tokens = $users->pluck('fcm_token')->filter()->values()->toArray();

        if (empty($tokens)) {
            Log::warning('No FCM tokens found for the provided users');
            return ['success' => 0, 'failure' => $users->count()];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send notification to a single token
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $message = $this->createMessage($title, $body, $data)
                ->withChangedTarget('token', $token);

            $this->messaging->send($message);

            Log::info("Push notification sent successfully to token: {$token}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send push notification: {$e->getMessage()}", [
                'token' => $token,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        try {
            $message = $this->createMessage($title, $body, $data);

            $report = $this->messaging->sendMulticast($message, $tokens);

            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            Log::info("Push notification multicast completed", [
                'success' => $successCount,
                'failure' => $failureCount,
            ]);

            // Log failed tokens for debugging
            if ($failureCount > 0) {
                foreach ($report->failures()->getItems() as $failure) {
                    Log::warning("Failed to send to token", [
                        'token' => $failure->target()->value(),
                        'error' => $failure->error()->getMessage(),
                    ]);
                }
            }

            return [
                'success' => $successCount,
                'failure' => $failureCount,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to send multicast push notification: {$e->getMessage()}", [
                'tokens_count' => count($tokens),
                'exception' => $e,
            ]);

            return [
                'success' => 0,
                'failure' => count($tokens),
            ];
        }
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $message = $this->createMessage($title, $body, $data)
                ->withChangedTarget('topic', $topic);

            $this->messaging->send($message);

            Log::info("Push notification sent successfully to topic: {$topic}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send push notification to topic: {$e->getMessage()}", [
                'topic' => $topic,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Create a cloud message with notification and data
     */
    protected function createMessage(string $title, string $body, array $data = []): CloudMessage
    {
        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification);

        if (!empty($data)) {
            $message = $message->withData($data);
        }

        return $message;
    }

    /**
     * Validate FCM token
     */
    public function validateToken(string $token): bool
    {
        try {
            $this->messaging->validateRegistrationTokens([$token]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
