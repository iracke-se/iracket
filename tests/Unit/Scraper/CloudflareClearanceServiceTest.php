<?php

use App\Services\Scraper\CloudflareClearanceService;
use Illuminate\Support\Facades\Cache;

/**
 * The service shells out to scripts/scraper/cf_clearance.py; these tests
 * replace the python binary with a tiny shell stub so no browser is needed.
 */
function stubClearanceScript(string $stdout, int $exit = 0): string
{
    $path = sys_get_temp_dir() . '/cf_clearance_stub_' . uniqid() . '.sh';
    file_put_contents($path, "#!/bin/sh\nprintf '%s\\n' " . escapeshellarg($stdout) . "\nexit {$exit}\n");
    chmod($path, 0755);

    return $path;
}

beforeEach(function () {
    Cache::forget(CloudflareClearanceService::CACHE_KEY);
    config(['scraper.cloudflare.enabled' => true]);
});

it('parses the clearance json line and caches it', function () {
    $json = json_encode([
        'user_agent' => 'Mozilla/5.0 test-ua',
        'cookies' => [['name' => 'cf_clearance', 'value' => 'abc', 'domain' => '.profixio.com', 'path' => '/']],
        'cleared_in' => 3.1,
    ]);
    config(['scraper.python.binary' => stubClearanceScript("[INFO] noise on stdout\n" . $json)]);

    $service = app(CloudflareClearanceService::class);
    $clearance = $service->get();

    expect($clearance['user_agent'])->toBe('Mozilla/5.0 test-ua')
        ->and($clearance['cookies'][0]['name'])->toBe('cf_clearance')
        ->and($clearance['cleared_in'])->toBe(3.1)
        ->and($service->cached()['user_agent'])->toBe('Mozilla/5.0 test-ua');

    // A second call must not shell out again: swap the binary for a failing one.
    config(['scraper.python.binary' => stubClearanceScript('', 1)]);
    expect($service->get()['user_agent'])->toBe('Mozilla/5.0 test-ua');
});

it('exposes the clearance as environment for the python scrapers', function () {
    $json = json_encode([
        'user_agent' => 'ua-1',
        'cookies' => [['name' => 'cf_clearance', 'value' => 'xyz', 'domain' => '.profixio.com', 'path' => '/']],
    ]);
    config(['scraper.python.binary' => stubClearanceScript($json)]);

    $env = app(CloudflareClearanceService::class)->env();

    expect($env['SCRAPER_USER_AGENT'])->toBe('ua-1')
        ->and(json_decode($env['SCRAPER_CF_COOKIES'], true)[0]['value'])->toBe('xyz');
});

it('throws a helpful error when the challenge is not cleared', function () {
    config(['scraper.python.binary' => stubClearanceScript('', 1), 'scraper.browser.display' => ':9']);

    app(CloudflareClearanceService::class)->get();
})->throws(RuntimeException::class, 'DISPLAY=:9');

it('is a no-op when disabled', function () {
    config(['scraper.cloudflare.enabled' => false, 'scraper.python.binary' => stubClearanceScript('', 1)]);

    $service = app(CloudflareClearanceService::class);

    expect($service->get())->toBeNull()
        ->and($service->env())->toBe([]);
});
