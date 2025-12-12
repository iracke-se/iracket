<?php

namespace App\Console\Commands;

use Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RegenerateAppleSecret extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:regenerate-secret {--force : Force regeneration even if not expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically regenerate Apple Sign In client secret (runs every 5 months)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if we need to regenerate
        $lastGenerated = Cache::get('apple_secret_generated_at');
        $fiveMonthsAgo = now()->subMonths(5);

        if (!$this->option('force') && $lastGenerated && $lastGenerated > $fiveMonthsAgo) {
            $nextRegeneration = $lastGenerated->copy()->addMonths(5);
            $this->info('Apple client secret is still valid.');
            $this->line("Next regeneration scheduled for: {$nextRegeneration->format('Y-m-d H:i:s')}");

            return Command::SUCCESS;
        }

        $this->info('Regenerating Apple Sign In client secret...');

        // Get configuration from .env
        $teamId = config('services.apple.team_id');
        $clientId = config('services.apple.client_id');
        $keyId = config('services.apple.key_id');
        $keyPath = config('services.apple.key_path');

        // Validate configuration
        if (!$teamId || !$clientId || !$keyId || !$keyPath) {
            $this->error('Missing Apple Sign In configuration!');
            Log::error('Apple secret regeneration failed: Missing configuration');

            return Command::FAILURE;
        }

        // Build full path to P8 key
        $fullKeyPath = storage_path('app/apple/' . $keyPath);

        // Check if key file exists
        if (!File::exists($fullKeyPath)) {
            $this->error("P8 key file not found at: {$fullKeyPath}");
            Log::error("Apple secret regeneration failed: P8 key not found at {$fullKeyPath}");

            return Command::FAILURE;
        }

        try {
            // Read the private key
            $privateKey = File::get($fullKeyPath);

            // Create JWT payload
            $payload = [
                'iss' => $teamId,
                'iat' => time(),
                'exp' => time() + (86400 * 180), // 6 months
                'aud' => 'https://appleid.apple.com',
                'sub' => $clientId,
            ];

            // Generate client secret
            $clientSecret = JWT::encode($payload, $privateKey, 'ES256', $keyId);

            // Store the new secret in cache (valid for 6 months)
            Cache::put('apple_client_secret', $clientSecret, now()->addMonths(6));
            Cache::put('apple_secret_generated_at', now(), now()->addMonths(6));

            // Update .env file
            $this->updateEnvFile('APPLE_CLIENT_SECRET', $clientSecret);

            // Clear config cache to load new .env value
            $this->call('config:clear');

            $this->newLine();
            $this->info('✓ Apple client secret regenerated successfully!');
            $this->line('New secret has been saved to .env file');
            $this->line('Config cache cleared - new secret is active');
            $this->line('Token expires in 6 months, will auto-regenerate in 5 months');

            Log::info('Apple client secret regenerated successfully', [
                'expires_at' => now()->addMonths(6)->toDateTimeString(),
                'next_regeneration' => now()->addMonths(5)->toDateTimeString(),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to regenerate client secret: ' . $e->getMessage());
            Log::error('Apple secret regeneration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Update the .env file with new value
     */
    private function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        // Check if key exists
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            // Update existing key
            $envContent = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $envContent
            );
        } else {
            // Add new key
            $envContent .= "\n{$key}={$value}\n";
        }

        File::put($envPath, $envContent);
    }
}
