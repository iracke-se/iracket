<?php

namespace App\Jobs\Scraper;

use App\Services\Scraper\TransitionsScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeTransitionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour
    public int $tries = 3;
    public array $backoff = [60, 300, 600];

    protected array $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
        $this->onQueue(config('scraper.queue.queue_name', 'scraper'));
    }

    public function handle(TransitionsScraper $scraper): void
    {
        $scraper->scrape($this->parameters);
    }
}
