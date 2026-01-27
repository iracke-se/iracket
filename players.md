# Fix SBTF Player Data Transfer in ConnectAccount

## Problem

When a user connects their account to an SBTF (Swedish Table Tennis Federation) player profile in the ConnectAccount component, the system only transfers the SBTF identifiers but not the actual player profile data.

**Current behavior:**
- User registers as "Damjan Test"
- User connects to SBTF player "Elias Björnsjö Ranefur"
- Result: Account still shows "Damjan Test" instead of the official SBTF name

**Root cause:**
In `app/Livewire/Auth/ConnectAccount.php` lines 127-129, only these fields are transferred:
- `sbtf_player_id`
- `sbtf_synced`
- `sbtf_synced_at`

The official player data (first_name, last_name, gender, birth_year) is NOT transferred, causing the registered name to override the authoritative SBTF data.

## Solution Overview

Transfer the complete player profile data from the SBTF player to the current user account, while preserving the user's originally registered name in the `user_fullname` field for reference.

### Fields to Transfer

1. **first_name** - Official SBTF first name (authoritative)
2. **last_name** - Official SBTF last name (authoritative)
3. **gender** - Transfer if SBTF player has it
4. **birth_year** - Transfer if SBTF player has it
5. **user_fullname** - Preserve user's registered name before overwriting

**Note:** `club_id` is already handled correctly through the existing UI flow (lines 68-72 auto-select club from player, line 147 uses the auto-selected value).

## Implementation Details

### File to Modify

**`app/Livewire/Auth/ConnectAccount.php`**

#### 1. Add Import Statement (around line 8)

```php
use Illuminate\Support\Facades\Log;
```

#### 2. Replace Lines 125-139

**Current code:**
```php
if ($existingPlayer && $existingPlayer->sbtf_synced) {
    // Transfer relevant data from the existing SBTF player to current user
    $user->sbtf_player_id = $existingPlayer->sbtf_player_id;
    $user->sbtf_synced = true;
    $user->sbtf_synced_at = $existingPlayer->sbtf_synced_at;

    // Transfer rankings if any
    $existingPlayer->monthlyRankings()->update(['user_id' => $user->id]);

    // Transfer matches if any
    $existingPlayer->matchesAsPlayer1()->update(['player1_id' => $user->id]);
    $existingPlayer->matchesAsPlayer2()->update(['player2_id' => $user->id]);

    // Delete the old player record
    $existingPlayer->delete();
}
```

**New code:**
```php
if ($existingPlayer && $existingPlayer->sbtf_synced) {
    // Validate that SBTF player has required data
    if (empty($existingPlayer->first_name) || empty($existingPlayer->last_name)) {
        Log::error('SBTF player has invalid name data', [
            'player_id' => $existingPlayer->id,
            'sbtf_player_id' => $existingPlayer->sbtf_player_id,
        ]);

        $this->addError('playerId', __('connect.invalid_sbtf_player_data'));
        return;
    }

    // Preserve the user's registered name before transferring SBTF data
    if (!$user->user_fullname) {
        $currentName = trim($user->first_name . ' ' . $user->last_name);
        if (!empty($currentName)) {
            $user->user_fullname = $currentName;
        }
    }

    // Transfer SBTF identifiers
    $user->sbtf_player_id = $existingPlayer->sbtf_player_id;
    $user->sbtf_synced = true;
    $user->sbtf_synced_at = $existingPlayer->sbtf_synced_at;

    // Transfer official SBTF player profile data (SBTF data is authoritative)
    $user->first_name = $existingPlayer->first_name;
    $user->last_name = $existingPlayer->last_name;

    // Transfer optional fields if available from SBTF player
    if (!empty($existingPlayer->gender)) {
        $user->gender = $existingPlayer->gender;
    }

    if (!empty($existingPlayer->birth_year)) {
        $user->birth_year = $existingPlayer->birth_year;
    }

    // Save all changes before transferring relationships
    $user->save();

    // Transfer rankings if any
    $existingPlayer->monthlyRankings()->update(['user_id' => $user->id]);

    // Transfer matches if any
    $existingPlayer->matchesAsPlayer1()->update(['player1_id' => $user->id]);
    $existingPlayer->matchesAsPlayer2()->update(['player2_id' => $user->id]);

    // Delete the old player record
    $existingPlayer->delete();
}
```

## Key Changes Explained

1. **Validation** (lines 3-10): Validates SBTF player has required name data before proceeding to prevent data corruption

2. **Preserve Registered Name** (lines 12-17): Stores the user's originally registered name in `user_fullname` before overwriting with official SBTF name

3. **Transfer Name Fields** (lines 26-27): Updates first_name and last_name with official SBTF data (authoritative source)

4. **Transfer Optional Fields** (lines 29-37): Transfers gender and birth_year if available in SBTF player

5. **Explicit Save** (line 39): Saves all user changes before transferring relationships to ensure data integrity

## Edge Cases Handled

- **User already has user_fullname** (OAuth registration): The `if (!$user->user_fullname)` check preserves existing registered name
- **SBTF player missing optional data**: Conditional transfers only copy fields that exist
- **Invalid SBTF player data**: Validation prevents connection if name fields are missing
- **Matching names**: If registered name equals SBTF name, both fields will match (UI already handles this gracefully)
- **Club selection**: Already handled correctly by existing code (lines 68-72, 147)

## Verification Testing

### Test Case 1: Standard Connect Flow
1. Create user "Damjan Test" via email registration
2. Login and navigate to `/connect-account`
3. Search for and select SBTF player "Elias Björnsjö Ranefur"
4. Click "Save"

**Expected results:**
- User profile displays name: "Elias Björnsjö Ranefur"
- Profile shows "Registered as: Damjan Test" (if names differ)
- User record in database:
  - `first_name` = "Elias"
  - `last_name` = "Björnsjö Ranefur"
  - `user_fullname` = "Damjan Test"
  - `sbtf_synced` = true
  - `gender` = (transferred from SBTF player)
  - `birth_year` = (transferred from SBTF player)
- Old SBTF player record is deleted
- Rankings transferred to user account
- Matches transferred to user account

### Test Case 2: OAuth User Connect
1. Register via Google as "John Doe" (user_fullname already set)
2. Connect to SBTF player "Jane Smith"

**Expected results:**
- Profile displays: "Jane Smith"
- Shows "Registered as: John Doe"
- `user_fullname` = "John Doe" (preserved from OAuth registration)

### Test Case 3: Matching Names
1. Register as "John Smith"
2. Connect to SBTF player "John Smith"

**Expected results:**
- Profile displays: "John Smith"
- NO "Registered as:" line shown (names match)
- `user_fullname` = "John Smith"

### Test Case 4: Data Validation
1. Attempt to connect to SBTF player with missing first_name or last_name

**Expected results:**
- Error message displayed
- Connection does not proceed
- User data unchanged

### Manual Testing Steps
```bash
# 1. Access the connect account page
ddev exec curl https://iracket.ddev.site/connect-account

# 2. In browser:
#    - Login with a test account
#    - Navigate to /connect-account
#    - Search for a player with Swedish characters
#    - Select the player
#    - Click "Save"

# 3. Verify in database
ddev exec mysql -e "SELECT id, first_name, last_name, user_fullname, sbtf_synced FROM users WHERE email='test@example.com';" -D db

# 4. Check player profile page shows correct name and "Registered as:" section
```

## Critical Files

- **`app/Livewire/Auth/ConnectAccount.php`** - Primary file to modify (connect method, lines 108-152)
- **`app/Models/User.php`** - Reference for fillable fields and relationships
- **`resources/views/livewire/user/players/show.blade.php`** - UI that displays both names (lines 61-65)
- **`app/Services/Scraper/SyncService.php`** - Reference pattern for SBTF data transfer (lines 252-315)

## Success Criteria

1. User's official name matches SBTF player name after connection
2. User's registered name is preserved in `user_fullname` field
3. Player profile page displays correct name with "Registered as:" when names differ
4. Gender and birth_year transferred when available
5. All relationships (rankings, matches) transferred correctly
6. Old SBTF player record deleted after successful merge
7. Error handling prevents connection with invalid SBTF data
