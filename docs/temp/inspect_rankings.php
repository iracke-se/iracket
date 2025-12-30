<?php

require __DIR__ . '/../../vendor/autoload.php';

use Spatie\Browsershot\Browsershot;

echo "=== RANKINGS PAGE INSPECTION ===\n";
echo "URL: https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender=m\n\n";

$js = <<<'JS'
(function() {
    const result = {
        periods: null,
        dateInputs: [],
        monthInputs: [],
        allSelects: []
    };

    // Get period dropdown
    const periodSelect = document.querySelector('[name="rid"]');
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
    const dateInputs = document.querySelectorAll('input[type="date"], input[type="month"]');
    dateInputs.forEach(input => {
        result.dateInputs.push({
            type: input.type,
            name: input.name || '',
            id: input.id || '',
            value: input.value || ''
        });
    });

    // Check for inputs with "month" or "date" in name
    const monthInputs = document.querySelectorAll('input[name*="month"], input[name*="date"], input[name*="period"]');
    monthInputs.forEach(input => {
        if (input.type !== 'date' && input.type !== 'month') {
            result.monthInputs.push({
                type: input.type,
                name: input.name || '',
                id: input.id || '',
                placeholder: input.placeholder || ''
            });
        }
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
    $result = Browsershot::url('https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender=m')
        ->setNodeBinary('/opt/homebrew/bin/node')
        ->setNpmBinary('/opt/homebrew/bin/npm')
        ->setChromePath('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome')
        ->timeout(30)
        ->waitUntilNetworkIdle()
        ->noSandbox()
        ->evaluate($js);

    $data = json_decode($result, true);

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

    echo "\nDATE/MONTH INPUTS:\n";
    if (count($data['dateInputs']) > 0) {
        foreach ($data['dateInputs'] as $input) {
            echo "  - Type: {$input['type']}, Name: {$input['name']}, ID: {$input['id']}\n";
        }
    } else {
        echo "  No date/month input fields found\n";
    }

    echo "\nOTHER MONTH/DATE RELATED INPUTS:\n";
    if (count($data['monthInputs']) > 0) {
        foreach ($data['monthInputs'] as $input) {
            echo "  - Type: {$input['type']}, Name: {$input['name']}, Placeholder: {$input['placeholder']}\n";
        }
    } else {
        echo "  No month-related input fields found\n";
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
                echo "  ✓ Month-level periods found (format: YYYY.MM.DD)\n";
                break;
            } elseif (preg_match('/Licens \d{4}-\d{2}/', $p['text'])) {
                $hasYearLevel = true;
            }
        }

        if (!$hasMonthLevel && $hasYearLevel) {
            echo "  ⊘ Only year-level periods found (format: Licens YYYY-YY)\n";
        }

        echo "\nConclusion:\n";
        if ($hasMonthLevel) {
            echo "  ✅ Month-level filtering IS POSSIBLE via period dropdown\n";
            echo "  The periods are already month-specific (YYYY.MM.DD format)\n";
        } else {
            echo "  ❌ Month-level filtering NOT available\n";
            echo "  The periods are year-based only (Licens format)\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
