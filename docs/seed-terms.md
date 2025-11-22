# Seed Terms Command

The `seed:terms` command seeds all terms content into the database, including Terms and Conditions, Privacy Policy, Bubbler explanation, and About Us pages.

## Usage

```bash
# Seed all terms (updates existing records, creates new ones)
php artisan seed:terms

# Fresh seed (truncates the terms table first, then seeds all content)
php artisan seed:terms --fresh
```

## What Gets Seeded

The command runs the following seeders in order:

| Seeder | Content | URL |
|--------|---------|-----|
| `TermsSeeder` | Terms and Conditions, Privacy Policy | `/terms/terms-and-conditions`, `/terms/privacy-policy` |
| `BubblerTermsSeeder` | Bubbler explanation and points system | `/terms/bubbler` |
| `AboutTermsSeeder` | About Us page | `/terms/about` |

## Seeder Files

All term seeders are located in `database/seeders/`:

- `TermsSeeder.php` - Terms and Conditions, Privacy Policy
- `BubblerTermsSeeder.php` - Bubbler ranking system explanation
- `AboutTermsSeeder.php` - About Us content

## Adding New Terms

To add a new terms page:

1. Create a new seeder file in `database/seeders/`:

```php
<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class YourNewTermsSeeder extends Seeder
{
    public function run(): void
    {
        Term::updateOrCreate(
            ['slug' => 'your-slug'],
            [
                'title' => 'Your Title',
                'content' => '
                    <h1>Your Title</h1>
                    <p>Your content here...</p>
                ',
                'is_active' => true,
            ]
        );
    }
}
```

2. Add the seeder to the `SeedTerms` command in `app/Console/Commands/SeedTerms.php`:

```php
$seeders = [
    'TermsSeeder' => 'Terms and Conditions, Privacy Policy',
    'BubblerTermsSeeder' => 'Bubbler',
    'AboutTermsSeeder' => 'About Us',
    'YourNewTermsSeeder' => 'Your New Page', // Add this line
];
```

3. Run the seed command:

```bash
php artisan seed:terms
```

4. Access your new page at `/terms/your-slug`

## HTML Content Guidelines

When creating content for terms pages, use the following HTML structure:

- `<h1>` - Main page title (only one per page)
- `<h2>` - Section headings
- `<h3>` - Sub-section headings
- `<p>` - Paragraphs
- `<ul>` / `<ol>` - Lists
- `<li>` - List items
- `<strong>` - Bold text
- `<a>` - Links
- `<table>` - Tables (with `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`)

All these elements are styled automatically by the terms page CSS.

## Database Structure

Terms are stored in the `terms` table with the following columns:

- `id` - Primary key
- `title` - Page title
- `slug` - URL slug (unique)
- `content` - HTML content
- `is_active` - Whether the page is publicly accessible
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Notes

- All seeders use `updateOrCreate()` to safely run multiple times without duplicating data
- The `--fresh` option will delete all existing terms before seeding
- Terms pages are accessible at `/terms/{slug}`
- Only active terms (`is_active = true`) are displayed
