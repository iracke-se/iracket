# Apple Sign In Key Storage

Place your Apple Sign In private key (`.p8` file) in this directory.

## Setup Instructions

1. Download your Apple Sign In key from [Apple Developer Portal](https://developer.apple.com/account/resources/authkeys/list)
2. Place the `AuthKey_XXXXX.p8` file in this directory
3. Configure your `.env` file with:
   ```env
   APPLE_TEAM_ID=YOUR_TEAM_ID
   APPLE_CLIENT_ID=com.webook.signin
   APPLE_KEY_ID=YOUR_KEY_ID
   APPLE_KEY_PATH=AuthKey_XXXXX.p8
   ```
4. Generate the client secret:
   ```bash
   php artisan apple:generate-secret
   ```
5. Copy the generated `APPLE_CLIENT_SECRET` to your `.env` file

## Automatic Regeneration

The Apple client secret automatically regenerates every 5 months via Laravel's scheduler:
- Scheduled to run daily at 02:30
- Only regenerates if 5+ months have passed since last generation
- Updates both `.env` file and cache automatically
- Tokens expire after 6 months, so regeneration at 5 months ensures no downtime

### Manual Commands

```bash
# Check when next regeneration is scheduled
php artisan apple:regenerate-secret

# Force regeneration immediately
php artisan apple:regenerate-secret --force
```

## Security Note

`.p8` files are automatically ignored by git and will not be committed to the repository.
