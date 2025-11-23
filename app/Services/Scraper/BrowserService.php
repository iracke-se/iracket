<?php

namespace App\Services\Scraper;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;

class BrowserService
{
    protected string $mainUrl;
    protected array $browserConfig;
    protected array $delays;

    public function __construct()
    {
        $this->mainUrl = config('scraper.main_url');
        $this->browserConfig = config('scraper.browser');
        $this->delays = config('scraper.delays');
    }

    /**
     * Create a new Browsershot instance with default configuration
     */
    public function createBrowser(): Browsershot
    {
        $browser = Browsershot::url($this->mainUrl)
            ->setNodeBinary($this->browserConfig['node_binary'])
            ->setNpmBinary($this->browserConfig['npm_binary'])
            ->timeout($this->browserConfig['timeout']);

        if ($this->browserConfig['wait_until_network_idle']) {
            $browser->waitUntilNetworkIdle();
        }

        if ($this->browserConfig['chrome_path']) {
            $browser->setChromePath($this->browserConfig['chrome_path']);
        }

        return $browser;
    }

    /**
     * Navigate to a URL and return Browsershot instance
     */
    public function navigateTo(string $url): Browsershot
    {
        $browser = Browsershot::url($url)
            ->setNodeBinary($this->browserConfig['node_binary'])
            ->setNpmBinary($this->browserConfig['npm_binary'])
            ->timeout($this->browserConfig['timeout'])
            ->waitUntilNetworkIdle();

        if ($this->browserConfig['chrome_path']) {
            $browser->setChromePath($this->browserConfig['chrome_path']);
        }

        return $browser;
    }

    /**
     * Execute JavaScript and return the result
     */
    public function evaluate(string $javascript, ?string $url = null): mixed
    {
        $browser = $url ? $this->navigateTo($url) : $this->createBrowser();

        return $browser->evaluate($javascript);
    }

    /**
     * Get all options from a dropdown by ID
     */
    public function getDropdownOptions(string $dropdownId): array
    {
        $js = $this->jsGetDropdownOptions($dropdownId);
        return $this->evaluate($js) ?? [];
    }

    /**
     * Get table data from a selector
     */
    public function getTableData(string $tableSelector): array
    {
        $js = $this->jsGetTableData($tableSelector);
        return $this->evaluate($js) ?? [];
    }

    /**
     * JavaScript: Get all values from a dropdown
     */
    public function jsGetDropdownOptions(string $id): string
    {
        return <<<JS
        (function () {
            var arr = [];
            var select = document.getElementById('{$id}');
            if (!select) return JSON.stringify(arr);
            for (let i = 0; i < select.options.length; i++) {
                arr.push({
                    value: select.options[i].value,
                    text: select.options[i].innerHTML.trim()
                });
            }
            return JSON.stringify(arr);
        })();
        JS;
    }

    /**
     * JavaScript: Get table data
     */
    public function jsGetTableData(string $selector): string
    {
        return <<<JS
        (function () {
            const rows = document.querySelectorAll('{$selector} tr');
            var result = Array.from(rows, row => {
                const columns = row.querySelectorAll('td');
                return Array.from(columns, column => column.innerText.trim());
            });
            return JSON.stringify(result);
        })();
        JS;
    }

    /**
     * JavaScript: Click an element and wait
     */
    public function jsClickAndWait(string $selector, int $waitMs = 500): string
    {
        return <<<JS
        (async function () {
            const element = document.querySelector('{$selector}');
            if (element) {
                element.click();
                await new Promise(resolve => setTimeout(resolve, {$waitMs}));
            }
            return true;
        })();
        JS;
    }

    /**
     * JavaScript: Select a dropdown value
     */
    public function jsSelectOption(string $selectId, string $value): string
    {
        return <<<JS
        (function () {
            const select = document.getElementById('{$selectId}');
            if (select) {
                select.value = '{$value}';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return true;
        })();
        JS;
    }

    /**
     * JavaScript: Get rankings data
     */
    public function jsGetRankings(string $selector): string
    {
        return <<<JS
        (function () {
            var rows = document.querySelectorAll('{$selector}');
            let result = [];
            for (var i = 1; i < rows.length; i++) {
                if (rows[i].cells && rows[i].cells.length >= 7) {
                    let data = {
                        position: rows[i].cells[0].innerText.trim(),
                        positionChange: rows[i].cells[1].innerText.trim(),
                        name: rows[i].cells[2].innerText.trim(),
                        born: rows[i].cells[3].innerText.trim(),
                        club: rows[i].cells[4].innerText.trim(),
                        points: rows[i].cells[5].innerText.trim(),
                        pointsChange: rows[i].cells[6].innerText.trim()
                    };
                    result.push(data);
                }
            }
            return JSON.stringify(result);
        })();
        JS;
    }

    /**
     * JavaScript: Get transitions data
     */
    public function jsGetTransitions(string $selector): string
    {
        return <<<JS
        (function () {
            let rows = document.querySelectorAll('{$selector}');
            let results = [];
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].className !== 'tabellhode' && rows[i].cells) {
                    let data = {
                        surname: rows[i].cells[0]?.innerText.trim() || '',
                        firstName: rows[i].cells[1]?.innerText.trim() || '',
                        born: rows[i].cells[2]?.innerText.trim() || '',
                        from: rows[i].cells[3]?.innerText.trim() || '',
                        to: rows[i].cells[4]?.innerText.trim() || '',
                        completionDate: rows[i].cells[5]?.innerText.trim() || ''
                    };
                    results.push(data);
                }
            }
            return JSON.stringify(results);
        })();
        JS;
    }

    /**
     * JavaScript: Get player list data
     */
    public function jsGetPlayerList(string $selector): string
    {
        return <<<JS
        (function () {
            const rows = document.querySelectorAll('{$selector} tr');
            let result = [];
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('td');
                if (cells.length > 0) {
                    result.push({
                        surname: cells[1]?.innerText.trim() || '',
                        firstName: cells[2]?.innerText.trim() || '',
                        sex: cells[3]?.innerText.trim() || '',
                        dateOfBirth: cells[4]?.innerText.trim() || '',
                        licenseType: cells[5]?.innerText.trim() || '',
                        playerClass: cells[6]?.innerText.trim() || ''
                    });
                }
            }
            return JSON.stringify(result);
        })();
        JS;
    }

    /**
     * JavaScript: Get division/match data
     */
    public function jsGetDivisionData(string $selector): string
    {
        return <<<JS
        (function () {
            let rows = document.querySelectorAll('{$selector}');
            let ar = [];
            for (let r = 0; r < rows.length; r++) {
                let rowData = { id: rows[r].id };
                let columns = rows[r].querySelectorAll('td');
                let coData = { name: '', score: '' };
                for (let i = 0; i < columns.length - 1; i++) {
                    if (i === 0) coData.name = columns[i].innerHTML;
                    if (i === 1) coData.score = columns[i].innerHTML;
                }
                rowData.values = coData;
                ar.push(rowData);
            }
            return JSON.stringify(ar);
        })();
        JS;
    }

    /**
     * JavaScript: Get match data
     */
    public function jsGetMatchData(string $selector): string
    {
        return <<<JS
        (function () {
            let rows = document.querySelectorAll('{$selector}');
            let ar = [];
            for (let r = 0; r < rows.length; r++) {
                let columns = rows[r].querySelectorAll('td');
                let coData = { id: rows[r].id, score: '' };
                for (let i = 0; i < columns.length; i++) {
                    if (i === 0) coData.part = columns[i].innerHTML;
                    if (i === 1) coData.player1 = columns[i].innerHTML;
                    if (i === 3) coData.player2 = columns[i].innerHTML;
                    if (i === 4) coData.score = columns[i].innerHTML;
                }
                ar.push(coData);
            }
            return JSON.stringify(ar);
        })();
        JS;
    }

    /**
     * Get configured delay in milliseconds
     */
    public function getDelay(string $type): int
    {
        return $this->delays[$type] ?? 300;
    }

    /**
     * Sleep for configured delay
     */
    public function delay(string $type): void
    {
        usleep($this->getDelay($type) * 1000);
    }

    /**
     * Get main URL
     */
    public function getMainUrl(): string
    {
        return $this->mainUrl;
    }

    /**
     * Get CSS selectors
     */
    public function getSelector(string $name): string
    {
        return config("scraper.selectors.{$name}", '');
    }
}
