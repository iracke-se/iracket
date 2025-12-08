# Bubbler & Points Calculation System

## What is Bubbler?

Bubbler is a ranking system that tracks **who performed best in the previous month**. It measures players based on points earned within their ranking class and region.

The system creates various leaderboards by combining:
- Rankings
- Gender
- Age groups
- Geography/Region

## Ranking Classes

### Women's Classes

| Class | Points Range |
|-------|-------------|
| Elite | 1750+ |
| Class 1 | 1500-1749 |
| Class 2 | 1250-1499 |
| Class 3 | 1000-1249 |
| Class 4 | 750-999 |
| Class 5 | 0-749 |

### Men's Classes

| Class | Points Range |
|-------|-------------|
| Elite | 2250+ |
| Class 1 | 2000-2249 |
| Class 2 | 1750-1999 |
| Class 3 | 1500-1749 |
| Class 4 | 1250-1499 |
| Class 5 | 1000-1249 |
| Class 6 | 750-999 |
| Class 7 | 0-749 |

### Alternative Range System

Generic ranges (used in some views):
- 0-499
- 500-999
- 1000-1249
- 1250-1499
- 1500-1749
- 1750-1999
- 2000-2249
- 2250+

## Class Transition Rules

**Important**: If a player earns points that would move them to a higher class, they remain in their **previous class** for that month's Bubbler ranking.

Example: A player with 1495 points who gains 100 points (reaching 1595) will still appear in their Class 2 (1250-1499) Bubbler for that month.

## Points Calculation

### How Points Are Calculated

When a match is recorded, the system:

1. **Calculate the point difference** between the two players
2. **Determine the interval** the difference falls into
3. **Identify the winner** and whether they had higher or lower ranking points
4. **Apply points** based on the official table below

### Points Table

| Point Difference | Higher Ranked Wins | Lower Ranked Wins |
|-----------------|-------------------|------------------|
| 0 - 25 | 10 points | 10 points |
| 26 - 50 | 9 points | 11 points |
| 51 - 75 | 8 points | 12 points |
| 76 - 100 | 7 points | 13 points |
| 101 - 125 | 6 points | 15 points |
| 126 - 150 | 6 points | 16 points |
| 151 - 200 | 5 points | 17 points |
| 201 - 250 | 4 points | 18 points |
| 251 - 300 | 3 points | 19 points |
| 301 - 400 | 2 points | 20 points |
| 401 - 500 | 2 points | 30 points |
| 501+ | 2 points | 40 points |

**Note**: The winner receives plus points and the loser receives the same number as minus points.

## Calculation Examples

### Example 1: Lower Ranked Player Wins

**Players:**
- Player 1: 1100 points
- Player 2: 1300 points

**Calculation:**
1. Point difference = 1300 - 1100 = **200 points**
2. Falls in **151-200 range**

**If Player 1 (lower ranked) wins:**
- Player 1: 1100 + 17 = **1117 points**
- Player 2: 1300 - 17 = **1283 points**

**If Player 2 (higher ranked) wins:**
- Player 1: 1100 - 5 = **1095 points**
- Player 2: 1300 + 5 = **1305 points**

### Example 2: Close Match

**Players:**
- Player 1: 1500 points
- Player 2: 1520 points

**Calculation:**
1. Point difference = 1520 - 1500 = **20 points**
2. Falls in **0-25 range**
3. Both scenarios award **10 points**

**If either player wins:**
- Winner: +10 points
- Loser: -10 points

### Example 3: Large Upset

**Players:**
- Player 1: 800 points
- Player 2: 1400 points

**Calculation:**
1. Point difference = 1400 - 800 = **600 points**
2. Falls in **501+ range**

**If Player 1 (lower ranked) wins:**
- Player 1: 800 + 40 = **840 points**
- Player 2: 1400 - 40 = **1360 points**

**If Player 2 (higher ranked) wins:**
- Player 1: 800 - 2 = **798 points**
- Player 2: 1400 + 2 = **1402 points**

## Implementation Details

### Database Tables

- `monthly_rankings` - Stores player rankings per month
  - `user_id` - Player ID
  - `year` - Year
  - `month` - Month
  - `rank` - Position in rankings
  - `points` - Total points
  - `points_change` - Points gained/lost that month

- `club_monthly_rankings` - Stores club rankings per month
  - `club_id` - Club ID
  - `year` - Year
  - `month` - Month
  - `rank` - Position in rankings
  - `points` - Total club points (sum of members)

### Match Recording Logic

```php
// Pseudo-code for points calculation
function calculateMatchPoints($player1Points, $player2Points, $winnerId) {
    $difference = abs($player1Points - $player2Points);

    // Determine points based on difference
    $points = match(true) {
        $difference <= 25 => 10,
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

    // Determine if winner was higher or lower ranked
    $higherRankedPlayer = $player1Points > $player2Points ? 1 : 2;
    $winnerIsHigherRanked = ($winnerId === $player1Id && $higherRankedPlayer === 1)
                          || ($winnerId === $player2Id && $higherRankedPlayer === 2);

    // Return appropriate points
    if (is_array($points)) {
        return $winnerIsHigherRanked ? $points[0] : $points[1];
    }
    return $points;
}
```

## Bubbler Display

### Top 3 Display

Each Bubbler tab (Ladies, Men, Clubs) shows:
1. **Top 3 prominently** - With medal indicators (1st, 2nd, 3rd)
2. **Remaining rankings** - In a table format

### Data Shown

For each player:
- Rank position
- Name
- Club
- Current points
- Points change (+/- from previous month)

### Filtering Options

Bubblers can be filtered by:
- Gender (Ladies/Men)
- Region/Geography
- Age group
- Ranking class

## Monthly Reset

At the start of each month:
1. Previous month's Bubbler is finalized
2. New month starts fresh with point tracking
3. Players are ranked within their current class
4. Top performers from previous month are highlighted

## Related Pages

- `/bubbler` - Main Bubbler leaderboard page
- `/terms/bubbler` - Bubbler explanation for users
- `/players` - Individual player profiles with ranking history
