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

    // Full export schedule settings (monthly on 1st)
    public bool $schedule_export_enabled = true;
    public string $schedule_export_day = '1';
    public string $schedule_export_time = '02:00';

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

        // Full export schedule settings
        $this->schedule_export_enabled = (bool) ScraperSetting::get('schedule_export_enabled', true);
        $this->schedule_export_day = ScraperSetting::get('schedule_export_day', '1') ?? '1';
        $this->schedule_export_time = ScraperSetting::get('schedule_export_time', '02:00') ?? '02:00';
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
            // Export schedule settings
            'schedule_export_enabled' => ['boolean'],
            'schedule_export_day' => ['required', 'integer', 'min:1', 'max:28'],
            'schedule_export_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
        ]);

        // Save URL settings
        ScraperSetting::set('url_players', $validated['url_players']);
        ScraperSetting::set('url_rankings', $validated['url_rankings']);
        ScraperSetting::set('url_transitions', $validated['url_transitions']);
        ScraperSetting::set('url_series', $validated['url_series']);
        ScraperSetting::set('url_live_center', $validated['url_live_center']);

        // Save export schedule settings
        ScraperSetting::set('schedule_export_enabled', $this->schedule_export_enabled ? '1' : '0');
        ScraperSetting::set('schedule_export_day', $validated['schedule_export_day']);
        ScraperSetting::set('schedule_export_time', $validated['schedule_export_time']);

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
            // Export schedule defaults
            'schedule_export_enabled' => '1',
            'schedule_export_day' => '1',
            'schedule_export_time' => '02:00',
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
