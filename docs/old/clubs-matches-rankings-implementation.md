# Clubs, Matches, Points & Rankings Implementation Plan

## Overview

This document outlines the implementation plan for the clubs, matches, points, and rankings system in iRacket.

---

## 1. Database Schema

### 1.1 Clubs Table
```php
Schema::create('clubs', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('logo')->nullable();
    $table->string('location')->nullable();
    $table->string('website')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->timestamps();
});
```

### 1.2 Users Table Update
```php
// Add club_id to users table
$table->foreignId('club_id')->nullable()->constrained()->nullOnDelete();
```

### 1.3 Matches Table
```php
Schema::create('matches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('player1_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('player2_id')->constrained('users')->onDelete('cascade');
    $table->date('played_at');
    $table->unsignedTinyInteger('player1_sets'); // Sets won by player 1
    $table->unsignedTinyInteger('player2_sets'); // Sets won by player 2
    $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
    $table->json('player1_comments')->nullable(); // Comments about player 1
    $table->json('player2_comments')->nullable(); // Comments about player 2
    $table->text('description')->nullable();
    $table->enum('status', ['pending', 'confirmed', 'disputed'])->default('pending');
    $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
    $table->timestamps();

    $table->index(['player1_id', 'played_at']);
    $table->index(['player2_id', 'played_at']);
});
```

### 1.4 Monthly Rankings Table
```php
Schema::create('monthly_rankings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->unsignedSmallInteger('year');
    $table->unsignedTinyInteger('month'); // 1-12
    $table->unsignedInteger('rank'); // Position
    $table->integer('points'); // Total points for that month
    $table->integer('points_change')->default(0); // +/- from previous month
    $table->timestamps();

    $table->unique(['user_id', 'year', 'month']);
    $table->index(['year', 'month', 'rank']);
});
```

### 1.5 Club Monthly Rankings Table
```php
Schema::create('club_monthly_rankings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('club_id')->constrained()->onDelete('cascade');
    $table->unsignedSmallInteger('year');
    $table->unsignedTinyInteger('month');
    $table->unsignedInteger('rank');
    $table->integer('total_points'); // Sum of all member points
    $table->timestamps();

    $table->unique(['club_id', 'year', 'month']);
});
```

---

## 2. Models

### 2.1 Club Model
```php
class Club extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'logo',
        'location', 'website', 'email', 'phone'
    ];

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function monthlyRankings()
    {
        return $this->hasMany(ClubMonthlyRanking::class);
    }
}
```

### 2.2 Match Model
```php
class Match extends Model
{
    protected $fillable = [
        'player1_id', 'player2_id', 'played_at',
        'player1_sets', 'player2_sets', 'winner_id',
        'player1_comments', 'player2_comments',
        'description', 'status', 'created_by'
    ];

    protected $casts = [
        'played_at' => 'date',
        'player1_comments' => 'array',
        'player2_comments' => 'array',
    ];

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### 2.3 MonthlyRanking Model
```php
class MonthlyRanking extends Model
{
    protected $fillable = [
        'user_id', 'year', 'month',
        'rank', 'points', 'points_change'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 2.4 User Model Updates
```php
// Add to User model
public function club()
{
    return $this->belongsTo(Club::class);
}

public function matchesAsPlayer1()
{
    return $this->hasMany(Match::class, 'player1_id');
}

public function matchesAsPlayer2()
{
    return $this->hasMany(Match::class, 'player2_id');
}

public function allMatches()
{
    return Match::where('player1_id', $this->id)
        ->orWhere('player2_id', $this->id);
}

public function monthlyRankings()
{
    return $this->hasMany(MonthlyRanking::class);
}

public function currentMonthRanking()
{
    return $this->monthlyRankings()
        ->where('year', now()->year)
        ->where('month', now()->month)
        ->first();
}
```

---

## 3. Routes Structure

```php
Route::middleware(['auth'])->group(function () {
    // Clubs
    Route::get('clubs', Clubs\Index::class)->name('clubs.index');
    Route::get('clubs/{club:slug}', Clubs\Show::class)->name('clubs.show');

    // Players
    Route::get('players', Players\Index::class)->name('players.index');
    Route::get('players/{user}', Players\Show::class)->name('players.show');

    // Matches
    Route::get('matches', Matches\Index::class)->name('matches.index');
    Route::get('matches/create', Matches\Form::class)->name('matches.create');
    Route::get('matches/{match}', Matches\Show::class)->name('matches.show');
    Route::get('matches/{match}/edit', Matches\Form::class)->name('matches.edit');

    // Bubbler
    Route::get('bubbler', Bubbler\Index::class)->name('bubbler.index');
});
```

---

## 4. Livewire Components

### 4.1 Clubs

#### `App\Livewire\User\Clubs\Index`
- List all clubs with search
- Show club logo, name, location, member count
- Link to club detail page

#### `App\Livewire\User\Clubs\Show`
- Club details (logo, name, description, contact info)
- List of club members with their rankings
- Club's monthly ranking history

### 4.2 Players

#### `App\Livewire\User\Players\Show`
**Top Section:**
- Profile picture (large)
- Name
- Club name (linked)
- Age
- Current month ranking position

**Rankings Table:**
| Month | Ranking | Points +/- |
|-------|---------|------------|
| Nov 2025 | #42 | +15 |
| Oct 2025 | #48 | -8 |
| ... | ... | ... |

### 4.3 Matches

#### `App\Livewire\User\Matches\Index`
**Top:** Year dropdown selector

**Content:** Matches grouped by month

**Match Card Layout:**
```
┌─────────────────────────────────────┐
│          15 Nov 2025                │
│            2 - 1                    │
│                                     │
│  [Avatar]          [Avatar]         │
│  Player 1    W-L   Player 2         │
│  Club Name         Club Name        │
└─────────────────────────────────────┘
```

#### `App\Livewire\User\Matches\Show`
- Full match details
- Both players with stats
- Comments and description
- Match result visualization

#### `App\Livewire\User\Matches\Form`
**Fields:**
1. **Date** - Date picker
2. **Opponent** - Searchable user select
3. **My Sets Won** - Dropdown (0-5)
4. **Opponent Sets Won** - Dropdown (0-5)
5. **Comments on Opponent** - Multi-select with suggestions:
   - Good backhand
   - Good forehand
   - Strong serve
   - Fast footwork
   - Super sensitive
   - Great sportsmanship
   - Custom comment input
6. **Description** - Textarea for match notes

**Validation:**
- Date required (not in future)
- Opponent required (not self)
- Sets required
- At least one player must have more sets (no ties)

### 4.4 Bubbler

#### `App\Livewire\User\Bubbler\Index`
**3 Tabs:**

1. **Ladies Tab**
   - Rankings table for female players
   - Columns: Rank, Player, Club, Points
   - Current month data

2. **Men Tab**
   - Rankings table for male players
   - Columns: Rank, Player, Club, Points
   - Current month data

3. **Clubs Tab**
   - Rankings table for clubs
   - Columns: Rank, Club, Total Points, Members
   - Current month data

---

## 5. Views Structure

```
resources/views/livewire/user/
├── clubs/
│   ├── index.blade.php
│   └── show.blade.php
├── players/
│   ├── index.blade.php (existing)
│   └── show.blade.php
├── matches/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── form.blade.php
└── bubbler/
    └── index.blade.php
```

---

## 6. Points System (Future Implementation)

> **Note:** Points calculation is not implemented in this phase. This is a placeholder for future development.

### Proposed Points Logic:
- Win: +10 base points
- Loss: -5 base points
- Bonus for winning against higher-ranked player
- Rankings updated monthly based on accumulated points

---

## 7. Implementation Order

### Phase 1: Database & Models
1. Create migrations for all tables
2. Create models with relationships
3. Update User model with new relationships
4. Create seeders for test data

### Phase 2: Clubs
1. Create Clubs Index page
2. Create Clubs Show page
3. Add club selector to user profile settings

### Phase 3: Player Profile
1. Create Players Show page
2. Display user info, club, rankings
3. Show rankings history table

### Phase 4: Matches
1. Create Matches Index page (my matches)
2. Create Match Form (create/edit)
3. Create Match Show page
4. Implement match validation

### Phase 5: Bubbler
1. Create Bubbler Index with tabs
2. Implement Ladies rankings
3. Implement Men rankings
4. Implement Clubs rankings

### Phase 6: Navigation Updates
1. Update bottom navbar links
2. Ensure all pages are accessible

---

## 8. Sample Data Seeders

### ClubsSeeder
- 5-10 sample clubs with Swedish names
- Locations in different Swedish cities

### MatchesSeeder
- Sample matches between test users
- Various dates across multiple months

### MonthlyRankingsSeeder
- Generate ranking data for past 6 months
- Different rankings for different users

---

## 9. UI/UX Considerations

- All pages use dark theme (zinc-800, zinc-700 backgrounds)
- Consistent with existing Information/Notifications styling
- Mobile-first responsive design
- Loading states for all async operations
- Empty states for no data scenarios

---

## 10. Questions to Clarify

1. **Match Confirmation:** Should the opponent confirm the match result, or is it auto-confirmed?
2. **Points Visibility:** Should points be visible to everyone or just the player?
3. **Club Management:** Can users create clubs, or only admins?
4. **Historical Data:** How many months of ranking history to display?
5. **Match Comments:** Are comments visible to both players or only the one who wrote them?

---

## Approval Checklist

Please review and confirm:

- [ ] Database schema looks correct
- [ ] Routes structure is appropriate
- [ ] Component breakdown makes sense
- [ ] UI layout matches expectations
- [ ] Implementation order is acceptable
- [ ] Any clarifications needed on questions above

Once approved, I'll begin implementation following the phase order outlined above.
