<?php

require __DIR__ . '/../../vendor/autoload.php';

use Spatie\Browsershot\Browsershot;

echo "=== SERIES PAGE INSPECTION ===\n";
echo "URL: https://www.profixio.com/fx/serieoppsett.php\n\n";

$loginUrl = 'https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT';

$js = <<<'JS'
(async function() {
    try {
        const response = await fetch('https://www.profixio.com/fx/serieoppsett.php', {
            credentials: 'include'
        });
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const result = {
            seasons: null,
            allSelects: []
        };

        // Get season dropdown
        const seasonSelect = doc.querySelector('[name="sesong"]');
        if (seasonSelect) {
            const options = [];
            for (let i = 0; i < Math.min(30, seasonSelect.options.length); i++) {
                options.push({
                    value: seasonSelect.options[i].value,
                    text: seasonSelect.options[i].innerHTML.trim()
                });
            }
            result.seasons = {
                total: seasonSelect.options.length,
                first30: options
            };
        }

        // Get all select elements
        const allSelects = doc.querySelectorAll('select');
        allSelects.forEach(select => {
            const firstOptions = [];
            for (let i = 0; i < Math.min(5, select.options.length); i++) {
                firstOptions.push(select.options[i].text.trim());
            }
            result.allSelects.push({
                name: select.name || '',
                optionsCount: select.options.length,
                firstOptions: firstOptions
            });
        });

        return JSON.stringify(result, null, 2);
    } catch (e) {
        return JSON.stringify({ error: e.message });
    }
})();
JS;

try {
    $result = Browsershot::url($loginUrl)
        ->setNodeBinary('/opt/homebrew/bin/node')
        ->setNpmBinary('/opt/homebrew/bin/npm')
        ->setChromePath('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome')
        ->timeout(30)
        ->waitUntilNetworkIdle()
        ->noSandbox()
        ->evaluate($js);

    $data = json_decode($result, true);

    if (isset($data['error'])) {
        echo "Error: {$data['error']}\n";
        exit(1);
    }

    echo "SEASON DROPDOWN:\n";
    if ($data['seasons']) {
        echo "  Total seasons: " . $data['seasons']['total'] . "\n";
        echo "  Seasons:\n";
        foreach ($data['seasons']['first30'] as $i => $s) {
            echo sprintf("    %2d. %-30s (value: %s)\n", $i+1, $s['text'], $s['value']);
        }
    } else {
        echo "  No season dropdown found\n";
    }

    echo "\nALL SELECT DROPDOWNS:\n";
    foreach ($data['allSelects'] as $select) {
        echo "  - Name: '{$select['name']}', Options: {$select['optionsCount']}\n";
    }

    // Analyze format
    echo "\n\nSEASON FORMAT ANALYSIS:\n";
    if ($data['seasons']) {
        $formats = [];
        foreach ($data['seasons']['first30'] as $s) {
            if (preg_match('/\d{4}\.(\d{2})/', $s['text'])) {
                $formats['month'] = true;
            } elseif (preg_match('/\d{4}[-\/]\d{2}/', $s['text'])) {
                $formats['year'] = true;
            }
        }

        if (isset($formats['month'])) {
            echo "  ✅ Month-level seasons found\n";
        } else {
            echo "  ❌ Month-level filtering NOT available\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
