<?php

namespace App\Livewire\Admin\Scraper;

use App\Models\Scraper\ScraperSetting;
use Livewire\Component;

class Settings extends Component
{
    // URL settings
    public string $url_players = '';
    public string $url_rankings = '';
    public string $url_transitions = '';
    public string $url_series = '';
    public string $url_live_center = '';

    // Schedule settings
    public bool $schedule_players_enabled = true;
    public string $schedule_players_frequency = 'monthly';
    public string $schedule_players_day = '1';
    public string $schedule_players_time = '03:00';

    public bool $schedule_rankings_enabled = true;
    public string $schedule_rankings_frequency = 'weekly';
    public string $schedule_rankings_day = 'sunday';
    public string $schedule_rankings_time = '02:00';

    public bool $schedule_transitions_enabled = false;
    public string $schedule_transitions_frequency = 'weekly';
    public string $schedule_transitions_day = 'monday';
    public string $schedule_transitions_time = '03:30';

    public bool $schedule_series_enabled = false;
    public string $schedule_series_frequency = 'weekly';
    public string $schedule_series_day = 'monday';
    public string $schedule_series_time = '04:00';

    public bool $schedule_live_center_enabled = false;
    public string $schedule_live_center_frequency = 'daily';
    public string $schedule_live_center_day = '';
    public string $schedule_live_center_time = '05:00';

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        // URL settings
        $this->url_players = ScraperSetting::get('url_players', '') ?? '';
        $this->url_rankings = ScraperSetting::get('url_rankings', '') ?? '';
        $this->url_transitions = ScraperSetting::get('url_transitions', '') ?? '';
        $this->url_series = ScraperSetting::get('url_series', '') ?? '';
        $this->url_live_center = ScraperSetting::get('url_live_center', '') ?? '';

        // Schedule settings
        $this->schedule_players_enabled = (bool) ScraperSetting::get('schedule_players_enabled', true);
        $this->schedule_players_frequency = ScraperSetting::get('schedule_players_frequency', 'monthly') ?? 'monthly';
        $this->schedule_players_day = ScraperSetting::get('schedule_players_day', '1') ?? '1';
        $this->schedule_players_time = ScraperSetting::get('schedule_players_time', '03:00') ?? '03:00';

        $this->schedule_rankings_enabled = (bool) ScraperSetting::get('schedule_rankings_enabled', true);
        $this->schedule_rankings_frequency = ScraperSetting::get('schedule_rankings_frequency', 'weekly') ?? 'weekly';
        $this->schedule_rankings_day = ScraperSetting::get('schedule_rankings_day', 'sunday') ?? 'sunday';
        $this->schedule_rankings_time = ScraperSetting::get('schedule_rankings_time', '02:00') ?? '02:00';

        $this->schedule_transitions_enabled = (bool) ScraperSetting::get('schedule_transitions_enabled', false);
        $this->schedule_transitions_frequency = ScraperSetting::get('schedule_transitions_frequency', 'weekly') ?? 'weekly';
        $this->schedule_transitions_day = ScraperSetting::get('schedule_transitions_day', 'monday') ?? 'monday';
        $this->schedule_transitions_time = ScraperSetting::get('schedule_transitions_time', '03:30') ?? '03:30';

        $this->schedule_series_enabled = (bool) ScraperSetting::get('schedule_series_enabled', false);
        $this->schedule_series_frequency = ScraperSetting::get('schedule_series_frequency', 'weekly') ?? 'weekly';
        $this->schedule_series_day = ScraperSetting::get('schedule_series_day', 'monday') ?? 'monday';
        $this->schedule_series_time = ScraperSetting::get('schedule_series_time', '04:00') ?? '04:00';

        $this->schedule_live_center_enabled = (bool) ScraperSetting::get('schedule_live_center_enabled', false);
        $this->schedule_live_center_frequency = ScraperSetting::get('schedule_live_center_frequency', 'daily') ?? 'daily';
        $this->schedule_live_center_day = ScraperSetting::get('schedule_live_center_day', '') ?? '';
        $this->schedule_live_center_time = ScraperSetting::get('schedule_live_center_time', '05:00') ?? '05:00';
    }

    public function updateSettings(): void
    {
        $validated = $this->validate([
            // URL settings
            'url_players' => ['required', 'url', 'max:500'],
            'url_rankings' => ['required', 'url', 'max:500'],
            'url_transitions' => ['required', 'url', 'max:500'],
            'url_series' => ['required', 'url', 'max:500'],
            'url_live_center' => ['required', 'url', 'max:500'],
            // Schedule settings
            'schedule_players_enabled' => ['boolean'],
            'schedule_players_frequency' => ['required', 'in:daily,weekly,monthly'],
            'schedule_players_day' => ['nullable', 'string', 'max:20'],
            'schedule_players_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'schedule_rankings_enabled' => ['boolean'],
            'schedule_rankings_frequency' => ['required', 'in:daily,weekly,monthly'],
            'schedule_rankings_day' => ['nullable', 'string', 'max:20'],
            'schedule_rankings_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'schedule_transitions_enabled' => ['boolean'],
            'schedule_transitions_frequency' => ['required', 'in:daily,weekly,monthly'],
            'schedule_transitions_day' => ['nullable', 'string', 'max:20'],
            'schedule_transitions_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'schedule_series_enabled' => ['boolean'],
            'schedule_series_frequency' => ['required', 'in:daily,weekly,monthly'],
            'schedule_series_day' => ['nullable', 'string', 'max:20'],
            'schedule_series_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'schedule_live_center_enabled' => ['boolean'],
            'schedule_live_center_frequency' => ['required', 'in:daily,weekly,monthly'],
            'schedule_live_center_day' => ['nullable', 'string', 'max:20'],
            'schedule_live_center_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
        ]);

        // Save URL settings
        ScraperSetting::set('url_players', $validated['url_players']);
        ScraperSetting::set('url_rankings', $validated['url_rankings']);
        ScraperSetting::set('url_transitions', $validated['url_transitions']);
        ScraperSetting::set('url_series', $validated['url_series']);
        ScraperSetting::set('url_live_center', $validated['url_live_center']);

        // Save schedule settings
        foreach (['players', 'rankings', 'transitions', 'series', 'live_center'] as $type) {
            ScraperSetting::set("schedule_{$type}_enabled", $this->{"schedule_{$type}_enabled"} ? '1' : '0');
            ScraperSetting::set("schedule_{$type}_frequency", $validated["schedule_{$type}_frequency"]);
            ScraperSetting::set("schedule_{$type}_day", $validated["schedule_{$type}_day"] ?? '');
            ScraperSetting::set("schedule_{$type}_time", $validated["schedule_{$type}_time"]);
        }

        // Clear all cached settings
        ScraperSetting::clearCache();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('admin-scraper.settings_saved'),
        ]);
    }

    public function resetToDefaults(): void
    {
        $defaults = [
            // URL defaults
            'url_players' => 'https://www.profixio.com/fx/lisens/public_oversikt.php?forb=SBTF.SE.BT',
            'url_rankings' => 'https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php',
            'url_transitions' => 'https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php',
            'url_series' => 'https://www.profixio.com/fx/serieoppsett.php',
            'url_live_center' => 'https://www.profixio.com/fx/livecenter/',
            // Schedule defaults
            'schedule_players_enabled' => '1',
            'schedule_players_frequency' => 'monthly',
            'schedule_players_day' => '1',
            'schedule_players_time' => '03:00',
            'schedule_rankings_enabled' => '1',
            'schedule_rankings_frequency' => 'weekly',
            'schedule_rankings_day' => 'sunday',
            'schedule_rankings_time' => '02:00',
            'schedule_transitions_enabled' => '0',
            'schedule_transitions_frequency' => 'weekly',
            'schedule_transitions_day' => 'monday',
            'schedule_transitions_time' => '03:30',
            'schedule_series_enabled' => '0',
            'schedule_series_frequency' => 'weekly',
            'schedule_series_day' => 'monday',
            'schedule_series_time' => '04:00',
            'schedule_live_center_enabled' => '0',
            'schedule_live_center_frequency' => 'daily',
            'schedule_live_center_day' => '',
            'schedule_live_center_time' => '05:00',
        ];

        foreach ($defaults as $key => $value) {
            ScraperSetting::set($key, $value);
        }

        ScraperSetting::clearCache();
        $this->loadSettings();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('admin-scraper.settings_reset'),
        ]);
    }

    public function render()
    {
        return view('livewire.admin.scraper.settings')
            ->layout('components.layouts.admin');
    }
}
