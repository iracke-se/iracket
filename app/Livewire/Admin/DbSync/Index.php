<?php

namespace App\Livewire\Admin\DbSync;

use App\Services\DbSync\DbSyncPusher;
use App\Services\DbSync\DbSyncReceiver;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    /** @var array<int, string> Selected table names. */
    public array $selected = [];

    /** Must be ticked to run a destructive (replace) sync. */
    public bool $confirmed = false;

    /** Result of the last "Test connection" click. */
    public ?array $ping = null;

    /** Password-gate state — keeps other admins from running a sync by accident. */
    public string $gatePassword = '';

    public bool $unlocked = false;

    public function mount(): void
    {
        // Unlock persists for the browser session once entered.
        $this->unlocked = (bool) session('db_sync_unlocked', false);
    }

    /**
     * Lightweight accidental-access guard (NOT real security) — the page is
     * hidden from the sidebar and gated behind a shared password.
     */
    public function unlock(): void
    {
        if ($this->gatePassword === '123456') {
            session(['db_sync_unlocked' => true]);
            $this->unlocked = true;
            $this->reset('gatePassword');

            return;
        }

        $this->reset('gatePassword');
        $this->addError('gate', 'Incorrect password.');
    }

    #[Computed]
    public function configured(): bool
    {
        return filled(config('datasync.target_url')) && filled(config('datasync.secret'));
    }

    #[Computed]
    public function mode(): string
    {
        return (string) config('datasync.mode', 'replace');
    }

    /** Syncable tables with row counts, excluding framework/runtime tables. */
    #[Computed]
    public function tables(): array
    {
        $ignored = (array) config('datasync.ignored_tables');
        $out = [];

        foreach (app(DbSyncReceiver::class)->realTables() as $t) {
            if (in_array($t, $ignored, true)) {
                continue;
            }
            $out[] = ['name' => $t, 'rows' => (int) DB::table($t)->count()];
        }

        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $out;
    }

    #[Computed]
    public function progress(): ?array
    {
        return app(DbSyncPusher::class)->state();
    }

    #[Computed]
    public function running(): bool
    {
        return ($this->progress()['status'] ?? null) === 'running';
    }

    public function selectAll(): void
    {
        $this->selected = array_column($this->tables(), 'name');
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function testConnection(): void
    {
        if (! $this->configured()) {
            $this->ping = ['ok' => false, 'status' => 0, 'body' => 'Set DB_SYNC_TARGET_URL and DB_SYNC_SECRET first.'];

            return;
        }

        $this->ping = app(DbSyncPusher::class)->ping();
    }

    public function startSync(): void
    {
        if (! $this->unlocked) {
            return;
        }

        $this->ping = null;
        $this->resetErrorBag('sync');

        if (! $this->configured()) {
            $this->addError('sync', 'This instance has no sync target configured.');

            return;
        }

        $tables = array_values(array_intersect(array_column($this->tables(), 'name'), $this->selected));

        if (empty($tables)) {
            $this->addError('sync', 'Pick at least one table to sync.');

            return;
        }

        if ($this->mode() === 'replace' && ! $this->confirmed) {
            $this->addError('sync', 'Tick the confirmation box — replace mode deletes production-only rows in the selected tables.');

            return;
        }

        app(DbSyncPusher::class)->begin($tables);
    }

    public function step(): void
    {
        if (! $this->unlocked) {
            return;
        }

        app(DbSyncPusher::class)->step();
    }

    public function clearRun(): void
    {
        app(DbSyncPusher::class)->clear();
        $this->confirmed = false;
    }

    public function render()
    {
        return view('livewire.admin.db-sync.index')->layout('components.layouts.admin');
    }
}
