# Auto Notification Service

The `AutoNotificationService` provides a reusable system for sending automatic in-app notifications to users.

## Location

```
app/Services/AutoNotificationService.php
```

## Available Notification Types

| Type | Constant | Description |
|------|----------|-------------|
| `match_created` | `TYPE_MATCH_CREATED` | When someone adds a match with you |
| `match_confirmed` | `TYPE_MATCH_CONFIRMED` | When opponent confirms your match |
| `match_rejected` | `TYPE_MATCH_REJECTED` | When opponent rejects your match |
| `ranking_update` | `TYPE_RANKING_UPDATE` | When your ranking changes |
| `achievement` | `TYPE_ACHIEVEMENT` | When you unlock an achievement |
| `bubbler` | `TYPE_BUBBLER` | Bubbler-related notifications |
| `follow` | `TYPE_FOLLOW` | When someone follows you |

## Usage

### Basic Setup

```php
use App\Services\AutoNotificationService;

// Get service instance
$notificationService = app(AutoNotificationService::class);
```

### Match Notifications

#### When a match is created

```php
$notificationService->matchCreated($match, $creator);
```

This notifies the opponent that a match was added with them.

**Example message:** "John Doe added a match with you (2-1). Please confirm or reject it."

#### When a match is confirmed

```php
$notificationService->matchConfirmed($match, $confirmer);
```

This notifies the match creator that their match was confirmed.

#### When a match is rejected

```php
$notificationService->matchRejected($match, $rejecter);
```

This notifies the match creator that their match was rejected.

### Ranking Notifications

```php
$notificationService->rankingUpdate($user, $oldRank, $newRank, $pointsChange);
```

**Parameters:**
- `$user` - The user whose ranking changed
- `$oldRank` - Previous ranking position
- `$newRank` - New ranking position
- `$pointsChange` - Points gained or lost (can be negative)

**Example message:** "You moved up from #5 to #3! (+25 points)"

### Achievement Notifications

```php
$notificationService->achievementUnlocked($user, $achievementName, $description);
```

**Example:**
```php
$notificationService->achievementUnlocked(
    $user,
    'First Victory',
    'Won your first match'
);
```

### Follower Notifications

```php
$notificationService->newFollower($user, $follower);
```

This notifies the user that someone started following them.

### Custom Notifications

For any custom notification type:

```php
$notificationService->custom(
    $user,
    'custom_type',      // Your custom type
    'Notification Title',
    'Notification message body',
    ['key' => 'value'], // Optional data array
    'path/to/icon.png'  // Optional custom icon
);
```

## Integration Example

Here's how the match creation uses the service:

```php
// In app/Livewire/User/Matches/Form.php

use App\Services\AutoNotificationService;

public function save()
{
    // ... validation and match creation ...

    $match = GameMatch::create($data);

    // Send notification to opponent
    $notificationService = app(AutoNotificationService::class);
    $notificationService->matchCreated($match, auth()->user());

    // ... rest of the method ...
}
```

## Notification Data Structure

All notifications are stored with:

| Field | Description |
|-------|-------------|
| `user_id` | The recipient user ID |
| `type` | Notification type (from constants) |
| `title` | Short title for the notification |
| `message` | Detailed message body |
| `data` | JSON array with additional data (URLs, IDs, etc.) |
| `icon` | Path to notification icon |
| `read_at` | Timestamp when read (null if unread) |

## Adding New Notification Types

1. Add a constant in `AutoNotificationService`:

```php
public const TYPE_NEW_FEATURE = 'new_feature';
```

2. Create a method for it:

```php
public function newFeature(User $user, string $featureName): void
{
    Notification::create([
        'user_id' => $user->id,
        'type' => self::TYPE_NEW_FEATURE,
        'title' => __('New Feature Available'),
        'message' => __(':feature is now available!', [
            'feature' => $featureName,
        ]),
        'data' => [
            'feature' => $featureName,
            'url' => route('features.show', $featureName),
        ],
        'icon' => 'assets/images/icon.png',
    ]);
}
```

3. Add icon handling in `notifications/index.blade.php` if needed:

```php
@case('new_feature')
    <svg class="w-5 h-5 ...">
        <!-- Your icon SVG -->
    </svg>
    @break
```

## Notes

- All notifications use the default brand logo (`assets/images/icon.png`) unless specified otherwise
- Messages support Laravel's localization using `__()` helper
- The `data` field can contain URLs for deep linking when user taps the notification
- Notifications appear in the user's notification center and show as a badge on the Info icon
