<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Process\Process;

/**
 * Obtains and caches the Cloudflare clearance profixio requires since 2026-09.
 *
 * profixio fronts ranking_sbtf_list.php and serieoppsett.php with a Cloudflare
 * managed challenge that Chromium cannot pass on a machine without a hardware
 * GPU. Camoufox (a stealth Firefox, headless) clears it in a few seconds and
 * receives a `cf_clearance` cookie bound to the server IP + user-agent. This
 * service runs scripts/scraper/cf_clearance.py to get that cookie, caches it,
 * and hands it to the scrapers:
 *
 *  - Python Playwright scrapers get it through the SCRAPER_CF_COOKIES /
 *    SCRAPER_USER_AGENT environment variables (see env()).
 *  - Browsershot scrapers get it through apply().
 *
 * A request that carries the cookie *and* the exact user-agent it was issued
 * for is served normally, headless or not.
 */
class CloudflareClearanceService
{
    public const CACHE_KEY = 'scraper:cf_clearance';

    public function enabled(): bool
    {
        return (bool) config('scraper.cloudflare.enabled', true);
    }

    /**
     * The cached clearance, obtaining a fresh one when none is cached (or when
     * $refresh is set). Returns null when the feature is disabled.
     *
     * @return array{user_agent: string, cookies: array<int, array<string, mixed>>, obtained_at: string}|null
     */
    public function get(bool $refresh = false): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        if (!$refresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && !empty($cached['cookies'])) {
                return $cached;
            }
        }

        $clearance = $this->obtain();
        Cache::put(self::CACHE_KEY, $clearance, (int) config('scraper.cloudflare.cache_ttl', 6 * 3600));

        return $clearance;
    }

    /**
     * The currently cached clearance without trying to obtain one.
     */
    public function cached(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        return is_array($cached) ? $cached : null;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Environment variables for the Python scrapers.
     *
     * @return array<string, string>
     */
    public function env(bool $refresh = false): array
    {
        $clearance = $this->get($refresh);
        if ($clearance === null) {
            return [];
        }

        return [
            'SCRAPER_USER_AGENT' => $clearance['user_agent'],
            'SCRAPER_CF_COOKIES' => json_encode($clearance['cookies']),
        ];
    }

    /**
     * Send the clearance with every request of a Browsershot instance.
     */
    public function apply(Browsershot $browser, bool $refresh = false): Browsershot
    {
        $clearance = $this->get($refresh);
        if ($clearance === null) {
            return $browser;
        }

        $cookies = [];
        foreach ($clearance['cookies'] as $cookie) {
            $cookies[$cookie['name']] = $cookie['value'];
        }

        return $browser
            ->userAgent($clearance['user_agent'])
            ->useCookies($cookies, '.profixio.com');
    }

    /**
     * Run cf_clearance.py on the configured display and parse its JSON line.
     *
     * @throws \RuntimeException when the challenge could not be passed
     */
    protected function obtain(): array
    {
        $script = base_path('scripts/scraper/cf_clearance.py');
        $arguments = [
            config('scraper.python.binary', 'python3'),
            $script,
            '--url', config('scraper.cloudflare.challenge_url'),
            '--timeout', (string) config('scraper.cloudflare.timeout', 90),
        ];

        $env = array_merge(getenv(), array_filter([
            'DISPLAY' => config('scraper.browser.display', ':0'),
            'XDG_RUNTIME_DIR' => config('scraper.browser.xdg_runtime_dir'),
            'PUPPETEER_EXECUTABLE_PATH' => config('scraper.browser.chrome_path'),
        ]));

        $process = new Process($arguments, base_path(), $env);
        $process->setTimeout((int) config('scraper.cloudflare.timeout', 90) + 60);

        Log::channel(config('scraper.logging.channel', 'stack'))
            ->info('Obtaining Cloudflare clearance', ['display' => $env['DISPLAY']]);

        $process->run();

        $line = null;
        foreach (array_reverse(explode("\n", trim($process->getOutput()))) as $candidate) {
            $decoded = json_decode($candidate, true);
            if (is_array($decoded) && isset($decoded['user_agent'], $decoded['cookies'])) {
                $line = $decoded;
                break;
            }
        }

        if (!$process->isSuccessful() || $line === null) {
            $stderr = trim($process->getErrorOutput());
            throw new \RuntimeException(
                "Could not obtain a Cloudflare clearance for profixio (exit {$process->getExitCode()}). "
                . "The scraper's Python needs camoufox: "
                . '<venv>/bin/pip install "camoufox[geoip]" && <venv>/bin/python3 -m camoufox fetch'
                . " (see SCRAPER.md § Cloudflare).\n"
                . ($stderr !== '' ? substr($stderr, -2000) : '(no output from cf_clearance.py)')
            );
        }

        if (empty($line['cookies'])) {
            throw new \RuntimeException(
                'cf_clearance.py passed the challenge but received no cf_clearance cookie — '
                . 'profixio may have changed its protection.'
            );
        }

        Log::channel(config('scraper.logging.channel', 'stack'))
            ->info('Cloudflare clearance obtained', [
                'cleared_in' => $line['cleared_in'] ?? null,
                'user_agent' => $line['user_agent'],
            ]);

        return [
            'user_agent' => $line['user_agent'],
            'cookies' => $line['cookies'],
            'cleared_in' => $line['cleared_in'] ?? null,
            'obtained_at' => now()->toIso8601String(),
        ];
    }
}
