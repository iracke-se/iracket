<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class BaseScraperService
{
    protected BrowserService $browserService;
    protected ScraperRun $run;
    protected array $retryConfig;

    public function __construct(BrowserService $browserService)
    {
        $this->browserService = $browserService;
        $this->retryConfig = config('scraper.retry');
    }

    /**
     * Get the scraper type identifier
     */
    abstract public function getType(): string;

    /**
     * Execute the scraping logic
     */
    abstract protected function execute(): void;

    /**
     * Start the scraping process
     */
    public function scrape(array $parameters = []): ScraperRun
    {
        // Create a new run
        $this->run = ScraperRun::create([
            'type' => $this->getType(),
            'status' => ScraperRun::STATUS_PENDING,
            'parameters' => $parameters,
        ]);

        try {
            $this->run->markAsRunning();
            $this->run->info('Starting scrape', $parameters);

            $this->execute();

            $this->run->markAsCompleted();
            $this->run->info('Scrape completed', [
                'items_scraped' => $this->run->items_scraped,
                'items_failed' => $this->run->items_failed,
            ]);

            Log::channel(config('scraper.logging.channel', 'stack'))
                ->info("Scrape completed: {$this->getType()}", [
                    'run_id' => $this->run->id,
                    'items_scraped' => $this->run->items_scraped,
                ]);

        } catch (Exception $e) {
            $this->run->markAsFailed($e->getMessage());
            $this->run->error('Scrape failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Log::channel(config('scraper.logging.channel', 'stack'))
                ->error("Scrape failed: {$this->getType()}", [
                    'run_id' => $this->run->id,
                    'error' => $e->getMessage(),
                ]);

            throw $e;
        }

        return $this->run;
    }

    /**
     * Execute with retry logic
     */
    protected function withRetry(callable $callback, string $context = ''): mixed
    {
        $attempts = 0;
        $maxAttempts = $this->retryConfig['max_attempts'];
        $lastException = null;

        while ($attempts < $maxAttempts) {
            try {
                return $callback();
            } catch (Exception $e) {
                $attempts++;
                $lastException = $e;

                if ($attempts < $maxAttempts) {
                    $delay = $this->retryConfig['backoff'][$attempts - 1] ?? $this->retryConfig['delay_ms'];

                    $this->run->warning("Retry attempt {$attempts}/{$maxAttempts}", [
                        'context' => $context,
                        'error' => $e->getMessage(),
                        'delay_seconds' => $delay,
                    ]);

                    sleep($delay);
                }
            }
        }

        $this->run->incrementFailed();
        throw $lastException;
    }

    /**
     * Log info message
     */
    protected function info(string $message, array $context = []): void
    {
        $this->run->info($message, $context);

        if (config('scraper.logging.detailed')) {
            Log::channel(config('scraper.logging.channel', 'stack'))
                ->info($message, $context);
        }
    }

    /**
     * Log warning message
     */
    protected function warning(string $message, array $context = []): void
    {
        $this->run->warning($message, $context);

        Log::channel(config('scraper.logging.channel', 'stack'))
            ->warning($message, $context);
    }

    /**
     * Log error message
     */
    protected function error(string $message, array $context = []): void
    {
        $this->run->error($message, $context);

        Log::channel(config('scraper.logging.channel', 'stack'))
            ->error($message, $context);
    }

    /**
     * Delay between operations
     */
    protected function delay(string $type = 'between_requests'): void
    {
        $this->browserService->delay($type);
    }

    /**
     * Get parameter from run
     */
    protected function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->run->parameters[$key] ?? $default;
    }

    /**
     * Check if should continue scraping
     */
    protected function shouldContinue(): bool
    {
        // Refresh the run to check if it's been cancelled
        $this->run->refresh();
        return $this->run->status === ScraperRun::STATUS_RUNNING;
    }

    /**
     * Get the current run
     */
    public function getRun(): ScraperRun
    {
        return $this->run;
    }

    /**
     * Extract year from period text (standardized across all scrapers)
     *
     * Handles formats:
     * - "Licens 2024-25" → 2024
     * - "Licens 2024/25" → 2024
     * - "2024.01.01" → 2024
     * - "January 2024" → 2024
     * - "2024" → 2024
     *
     * @param string $periodText The period text from profixio.com
     * @return int|null The extracted year as integer, or null if not found
     */
    protected function extractYearFromPeriod(string $periodText): ?int
    {
        // Pattern 1: "Licens 2024-25" or "Licens 2024/25"
        if (preg_match('/(\d{4})[-\/]\d{2}/', $periodText, $matches)) {
            return (int)$matches[1];
        }

        // Pattern 2: "2024.01.01" (dot-separated date)
        if (preg_match('/(\d{4})\.(\d{2})\.(\d{2})/', $periodText, $matches)) {
            return (int)$matches[1];
        }

        // Pattern 3: Any 4-digit year in text
        if (preg_match('/(\d{4})/', $periodText, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
