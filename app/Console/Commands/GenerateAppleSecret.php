<?php

namespace App\Console\Commands;

use Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateAppleSecret extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:generate-secret';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Apple Sign In client secret using the P8 key';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating Apple Sign In client secret...');
        $this->newLine();

        // Get configuration from .env
        $teamId = config('services.apple.team_id');
        $clientId = config('services.apple.client_id');
        $keyId = config('services.apple.key_id');
        $keyPath = config('services.apple.key_path');

        // Validate configuration
        if (!$teamId || !$clientId || !$keyId || !$keyPath) {
            $this->error('Missing Apple Sign In configuration!');
            $this->newLine();
            $this->line('Please ensure the following are set in your .env file:');
            $this->line('  - APPLE_TEAM_ID');
            $this->line('  - APPLE_CLIENT_ID');
            $this->line('  - APPLE_KEY_ID');
            $this->line('  - APPLE_KEY_PATH');

            return Command::FAILURE;
        }

        // Build full path to P8 key
        $fullKeyPath = storage_path('app/apple/' . $keyPath);

        // Check if key file exists
        if (!File::exists($fullKeyPath)) {
            $this->error("P8 key file not found at: {$fullKeyPath}");
            $this->newLine();
            $this->line('Please place your Apple AuthKey_*.p8 file in storage/app/apple/');

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

            $this->info('✓ Client secret generated successfully!');
            $this->newLine();
            $this->line('Add this to your .env file:');
            $this->newLine();
            $this->line('APPLE_CLIENT_SECRET=' . $clientSecret);
            $this->newLine();
            $this->comment('Note: This token expires in 6 months. Re-run this command to generate a new one.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate client secret: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
