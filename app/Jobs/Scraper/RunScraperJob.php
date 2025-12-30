<?php

namespace App\Jobs\Scraper;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunScraperJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200; // 2 hours
    public int $tries = 1;

    protected string $command;
    protected array $parameters;
    protected array $options;

    /**
     * Create a new job instance.
     *
     * @param string $command The scraper command to run (e.g., 'scraper:start', 'scraper:run')
     * @param array $parameters Command parameters (e.g., ['month' => '2024-12'])
     * @param array $options Command options (e.g., ['--all' => true])
     */
    public function __construct(string $command, array $parameters = [], array $options = [])
    {
        $this->command = $command;
        $this->parameters = $parameters;
        $this->options = $options;

        // Set higher timeout for full scrapes
        if ($command === 'scraper:start' && !isset($options['--limit-periods'])) {
            $this->timeout = 14400; // 4 hours for full scrape
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        Log::channel(config('scraper.logging.channel', 'stack'))
            ->info("Starting queued scraper job", [
                'command' => $this->command,
                'parameters' => $this->parameters,
                'options' => $this->options,
            ]);

        try {
            // Build command arguments
            $args = $this->parameters;
            foreach ($this->options as $key => $value) {
                if (is_bool($value) && $value) {
                    $args[$key] = true;
                } elseif (!is_bool($value)) {
                    $args[$key] = $value;
                }
            }

            // Run the artisan command
            $exitCode = Artisan::call($this->command, $args);

            $duration = round(microtime(true) - $startTime, 2);

            if ($exitCode === 0) {
                Log::channel(config('scraper.logging.channel', 'stack'))
                    ->info("Scraper job completed successfully", [
                        'command' => $this->command,
                        'duration' => $duration,
                        'exit_code' => $exitCode,
                    ]);
            } else {
                Log::channel(config('scraper.logging.channel', 'stack'))
                    ->error("Scraper job failed", [
                        'command' => $this->command,
                        'duration' => $duration,
                        'exit_code' => $exitCode,
                        'output' => Artisan::output(),
                    ]);

                throw new \Exception("Scraper command failed with exit code {$exitCode}");
            }
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);

            Log::channel(config('scraper.logging.channel', 'stack'))
                ->error("Scraper job exception", [
                    'command' => $this->command,
                    'duration' => $duration,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel(config('scraper.logging.channel', 'stack'))
            ->error("Scraper job failed permanently", [
                'command' => $this->command,
                'parameters' => $this->parameters,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
    }
}
