<?php

require __DIR__ . '/../../vendor/autoload.php';

use Spatie\Browsershot\Browsershot;

echo "=== PLAYERS PAGE INSPECTION ===\n";
echo "URL: https://www.profixio.com/fx/lisens/public_oversikt.php\n\n";

$js = <<<'JS'
(function() {
    const result = {
        periods: null,
        dateInputs: [],
        allSelects: []
    };

    // Get period dropdown
    const periodSelect = document.querySelector('[name="periodeid"]');
    if (periodSelect) {
        const options = [];
        for (let i = 0; i < Math.min(30, periodSelect.options.length); i++) {
            options.push({
                value: periodSelect.options[i].value,
                text: periodSelect.options[i].innerHTML.trim()
            });
        }
        result.periods = {
            total: periodSelect.options.length,
            first30: options
        };
    }

    // Check for date/month inputs
    const dateInputs = document.querySelectorAll('input[type="date"], input[type="month"], input[name*="month"], input[name*="date"]');
    dateInputs.forEach(input => {
        result.dateInputs.push({
            type: input.type,
            name: input.name || '',
            id: input.id || ''
        });
    });

    // Get all select elements
    const allSelects = document.querySelectorAll('select');
    allSelects.forEach(select => {
        const firstOptions = [];
        for (let i = 0; i < Math.min(5, select.options.length); i++) {
            firstOptions.push(select.options[i].text.trim());
        }
        result.allSelects.push({
            name: select.name || '',
            id: select.id || '',
            optionsCount: select.options.length,
            firstOptions: firstOptions
        });
    });

    return JSON.stringify(result, null, 2);
})();
JS;

try {
    // Need to login first
    $loginUrl = 'https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT';

    $combinedJs = <<<'COMBINEDJS'
(async function() {
    try {
        // Fetch the player list page
        const response = await fetch('https://www.profixio.com/fx/lisens/public_oversikt.php', {
            credentials: 'include'
        });
        const html = await response.text();

        // Parse HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const result = {
            periods: null,
            dateInputs: [],
            allSelects: []
        };

        // Get period dropdown
        const periodSelect = doc.querySelector('[name="periodeid"]');
        if (periodSelect) {
            const options = [];
            for (let i = 0; i < Math.min(30, periodSelect.options.length); i++) {
                options.push({
                    value: periodSelect.options[i].value,
                    text: periodSelect.options[i].innerHTML.trim()
                });
            }
            result.periods = {
                total: periodSelect.options.length,
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
                id: select.id || '',
                optionsCount: select.options.length,
                firstOptions: firstOptions
            });
        });

        return JSON.stringify(result, null, 2);
    } catch (e) {
        return JSON.stringify({ error: e.message });
    }
})();
COMBINEDJS;

    $result = Browsershot::url($loginUrl)
        ->setNodeBinary('/opt/homebrew/bin/node')
        ->setNpmBinary('/opt/homebrew/bin/npm')
        ->setChromePath('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome')
        ->timeout(30)
        ->waitUntilNetworkIdle()
        ->noSandbox()
        ->evaluate($combinedJs);

    $data = json_decode($result, true);

    if (isset($data['error'])) {
        echo "Error: {$data['error']}\n";
        exit(1);
    }

    echo "PERIOD DROPDOWN:\n";
    if ($data['periods']) {
        echo "  Total periods: " . $data['periods']['total'] . "\n";
        echo "  Format analysis:\n";
        foreach ($data['periods']['first30'] as $i => $p) {
            echo sprintf("    %2d. %-30s (value: %s)\n", $i+1, $p['text'], $p['value']);
        }
    } else {
        echo "  No period dropdown found\n";
    }

    echo "\nALL SELECT DROPDOWNS:\n";
    foreach ($data['allSelects'] as $select) {
        echo "  - Name: '{$select['name']}', Options: {$select['optionsCount']}\n";
        echo "    First options: " . implode(', ', $select['firstOptions']) . "\n";
    }

    // Analyze period format
    echo "\n\nPERIOD FORMAT ANALYSIS:\n";
    if ($data['periods']) {
        $hasMonthLevel = false;
        $hasYearLevel = false;

        foreach ($data['periods']['first30'] as $p) {
            if (preg_match('/\d{4}\.\d{2}\.\d{2}/', $p['text'])) {
                $hasMonthLevel = true;
                break;
            } elseif (preg_match('/Licens \d{4}-\d{2}/', $p['text'])) {
                $hasYearLevel = true;
            }
        }

        echo "\nConclusion:\n";
        if ($hasMonthLevel) {
            echo "  ✅ Month-level filtering IS POSSIBLE\n";
        } else {
            echo "  ❌ Month-level filtering NOT available (year-based only)\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
