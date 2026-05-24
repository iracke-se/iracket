# Points Calculation & Badges Implementation

## Overview

This document outlines the implementation plan for:
1. Automatic points calculation when matches are created
2. Monthly sync reconciliation with official SBTF data
3. Badge system for Bubbler top 3 rankings

---

## Part 1: Points Calculation System

### Current State
- Users manually enter matches at `/matches/create`
- No automatic points calculation
- Official SBTF data synced on first Monday of each month

### Required Implementation

#### 1.1 PointsCalculationService

Create `App\Services\PointsCalculationService`:

```php
class PointsCalculationService
{
    /**
     * Calculate points based on ranking difference
     * Returns [higherRankedWins, lowerRankedWins]
     */
    public function getPointsForDifference(int $difference): array
    {
        return match(true) {
            $difference <= 25 => [10, 10],
            $difference <= 50 => [9, 11],
            $difference <= 75 => [8, 12],
            $difference <= 100 => [7, 13],
            $difference <= 125 => [6, 15],
            $difference <= 150 => [6, 16],
            $difference <= 200 => [5, 17],
            $difference <= 250 => [4, 18],
            $difference <= 300 => [3, 19],
            $difference <= 400 => [2, 20],
            $difference <= 500 => [2, 30],
            default => [2, 40],
        };
    }

    /**
     * Calculate match result and return point changes
     */
    public function calculateMatchPoints(
        int $player1Points,
        int $player2Points,
        int $winnerId,
        int $player1Id,
        int $player2Id
    ): array
    {
        $difference = abs($player1Points - $player2Points);
        $pointsTable = $this->getPointsForDifference($difference);

        $higherRankedPlayerId = $player1Points >= $player2Points ? $player1Id : $player2Id;
        $winnerIsHigherRanked = $winnerId === $higherRankedPlayerId;

        $pointsChange = $winnerIsHigherRanked ? $pointsTable[0] : $pointsTable[1];

        return [
            'winner_points_change' => $pointsChange,
            'loser_points_change' => -$pointsChange,
            'player1_change' => $winnerId === $player1Id ? $pointsChange : -$pointsChange,
            'player2_change' => $winnerId === $player2Id ? $pointsChange : -$pointsChange,
        ];
    }
}
```

#### 1.2 Match Creation Flow

When a match is saved:

1. Get current points for both players from `MonthlyRanking`
2. Calculate point changes using `PointsCalculationService`
3. Update both players' `MonthlyRanking` records
4. Store point changes on the match record

#### 1.3 Database Changes

Add to `matches` table:
- `player1_points_before` - Player 1's points before match
- `player2_points_before` - Player 2's points before match
- `player1_points_change` - Points gained/lost by player 1
- `player2_points_change` - Points gained/lost by player 2
- `is_manual` - Boolean to track manual vs synced matches

Add to `monthly_rankings` table:
- `manual_points_change` - Track points from manual matches separately
- `synced_points` - Official points from SBTF sync

#### 1.4 Monthly Sync Reconciliation

On first Monday sync:
1. Fetch official SBTF rankings
2. For each player:
   - Store official points as `synced_points`
   - Keep `manual_points_change` for reference
   - Set `points` to official value (override)
3. Recalculate all ranks based on new points

---

## Part 2: Badge System

### Badge Types

| Badge | Icon | Color | Description |
|-------|------|-------|-------------|
| 1st Place | Trophy/Medal | Gold (#FFD700) | Top monthly performer |
| 2nd Place | Medal | Silver (#C0C0C0) | Second place |
| 3rd Place | Medal | Bronze (#CD7F32) | Third place |

### Badge Categories

- **Ladies Bubbler** - Top 3 women by monthly points
- **Men Bubbler** - Top 3 men by monthly points
- **Clubs Bubbler** - Top 3 clubs by combined points

### Display Locations

1. **Player Profile Page** (`/players/{user}`)
   - Show all badges earned
   - Display which month/category

2. **Player Cards** (in lists)
   - Small badge icon next to name
   - Current month badges only

3. **Bubbler Page** (`/bubbler`)
   - Already shows top 3 with medals
   - Enhance with badge styling

### Implementation

#### 2.1 Badge Component

Create `resources/views/components/badge.blade.php`:

```blade
@props(['rank', 'category' => null, 'size' => 'md'])

@php
$colors = [
    1 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    2 => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    3 => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
];

$icons = [
    1 => '🥇',
    2 => '🥈',
    3 => '🥉',
];

$sizes = [
    'sm' => 'text-xs px-1.5 py-0.5',
    'md' => 'text-sm px-2 py-1',
    'lg' => 'text-base px-3 py-1.5',
];
@endphp

@if($rank >= 1 && $rank <= 3)
<span class="inline-flex items-center gap-1 rounded-full font-medium {{ $colors[$rank] }} {{ $sizes[$size] }}">
    <span>{{ $icons[$rank] }}</span>
    @if($category)
        <span class="sr-only">{{ $category }}</span>
    @endif
</span>
@endif
```

#### 2.2 Get User Badges

Add to User model or create BadgeService:

```php
public function getCurrentBadges(): Collection
{
    $year = now()->year;
    $month = now()->month;

    $badges = collect();

    // Check ladies ranking
    if ($this->gender === 'female') {
        $ranking = MonthlyRanking::where('user_id', $this->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($ranking && $ranking->rank <= 3) {
            $badges->push([
                'rank' => $ranking->rank,
                'category' => 'ladies',
                'year' => $year,
                'month' => $month,
            ]);
        }
    }

    // Check men ranking
    if ($this->gender === 'male') {
        $ranking = MonthlyRanking::where('user_id', $this->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($ranking && $ranking->rank <= 3) {
            $badges->push([
                'rank' => $ranking->rank,
                'category' => 'men',
                'year' => $year,
                'month' => $month,
            ]);
        }
    }

    return $badges;
}
```

---

## Implementation Order

### Phase 1: Database Migrations
1. Add columns to `matches` table
2. Add columns to `monthly_rankings` table
3. Run migrations

### Phase 2: Points Calculation Service
1. Create `PointsCalculationService`
2. Add unit tests for all point difference scenarios
3. Integrate into match creation flow

### Phase 3: Match Form Updates
1. Update `Matches\Form` component
2. Calculate and display expected points before save
3. Apply points on match save

### Phase 4: Badge Component
1. Create badge Blade component
2. Add `getCurrentBadges()` to User model
3. Display on player profile
4. Display on player cards

### Phase 5: Monthly Sync Updates
1. Update scraper sync service
2. Handle point reconciliation
3. Preserve manual match history

---

## Testing Checklist

### Points Calculation
- [ ] Equal ranked players (0-25 diff) = 10 points
- [ ] Small upset (26-50 diff) = 11 points for lower ranked winner
- [ ] Large upset (501+ diff) = 40 points for lower ranked winner
- [ ] Points properly added/subtracted from rankings
- [ ] Match stores before/after points

### Badges
- [ ] Correct badge shown for rank 1, 2, 3
- [ ] No badge for rank 4+
- [ ] Badge appears on profile
- [ ] Badge appears on player cards
- [ ] Separate tracking for men/women/clubs

### Sync
- [ ] Official points override manual
- [ ] Manual match history preserved
- [ ] Ranks recalculated after sync

---

## Files to Create/Modify

### New Files
- `app/Services/PointsCalculationService.php`
- `resources/views/components/badge.blade.php`
- `database/migrations/xxxx_add_points_tracking_to_matches.php`
- `database/migrations/xxxx_add_sync_tracking_to_monthly_rankings.php`

### Modified Files
- `app/Livewire/User/Matches/Form.php` - Add points calculation
- `app/Models/User.php` - Add `getCurrentBadges()`
- `app/Models/MonthlyRanking.php` - Add sync fields
- `resources/views/livewire/user/players/index.blade.php` - Show badges
- `resources/views/livewire/user/players/show.blade.php` - Show badges
- `app/Services/Scraper/SyncService.php` - Handle reconciliation
