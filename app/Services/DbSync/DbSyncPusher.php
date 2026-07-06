<?php

namespace App\Services\DbSync;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sender side (runs on your DDEV machine).
 *
 * Streams the selected tables to the target instance in small batches. Work is
 * time-boxed per call so it can be driven by the UI's progress poll — no queue
 * worker needed. Progress + cursor live in the cache so a run resumes if the
 * page is closed or a request drops.
 */
class DbSyncPusher
{
    private const STATE_KEY = 'db_sync:state';

    /** Build a fresh sync plan and mark it running. */
    public function begin(array $tables): void
    {
        $tables = array_values(array_filter($tables, fn ($t) => $this->receiver()->isSyncable($t)));

        $meta = [];
        foreach ($tables as $t) {
            $pkCols = $this->primaryKeyColumns($t);
            $meta[$t] = [
                'total' => (int) DB::table($t)->count(),
                'done' => 0,
                'status' => 'pending',
                // Single-column PK enables keyset paging (fast, stable at any size).
                'pk' => count($pkCols) === 1 ? $pkCols[0] : null,
                // Columns to order by for offset paging when there's no single PK.
                'order' => ! empty($pkCols) ? $pkCols : $this->columns($t),
            ];
        }

        Cache::put(self::STATE_KEY, [
            'status' => empty($tables) ? 'idle' : 'running',
            'mode' => (string) config('datasync.mode', 'replace'),
            'target' => (string) config('datasync.target_url'),
            'plan' => $tables,
            'pos' => 0,
            'sub' => $this->freshSub(),
            'tables' => $meta,
            'error' => null,
            'started_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], now()->addHours(6));
    }

    /** Do a time-boxed slice of the plan; called repeatedly by the UI poll. */
    public function step(): void
    {
        $state = Cache::get(self::STATE_KEY);
        if (! $state || $state['status'] !== 'running') {
            return;
        }

        $deadline = microtime(true) + (float) config('datasync.tick_budget', 4.0);
        $chunk = max(1, (int) config('datasync.chunk_size', 500));
        $mode = $state['mode'];

        try {
            while (microtime(true) < $deadline) {
                if ($state['pos'] >= count($state['plan'])) {
                    $state['status'] = 'completed';
                    break;
                }

                $table = $state['plan'][$state['pos']];
                $meta = $state['tables'][$table];
                $meta['status'] = 'running';

                // Empty table: in replace mode still clear the target copy once.
                if ($meta['total'] === 0) {
                    if ($mode === 'replace') {
                        $this->send($table, $mode, true, []);
                    }
                    $state = $this->finishTable($state, $table, $meta);
                    continue;
                }

                $rows = $this->fetchChunk($table, $meta, $state['sub'], $chunk);

                if ($rows->isEmpty()) {
                    $state = $this->finishTable($state, $table, $meta);
                    continue;
                }

                $firstBatch = ! $state['sub']['truncated'];
                $this->send($table, $mode, $firstBatch, $rows->map(fn ($r) => (array) $r)->all());
                $state['sub']['truncated'] = true;

                // Advance the cursor.
                if ($meta['pk']) {
                    $state['sub']['last_id'] = $rows->last()->{$meta['pk']};
                } else {
                    $state['sub']['offset'] += $rows->count();
                }

                $meta['done'] += $rows->count();
                $state['tables'][$table] = $meta;
            }
        } catch (\Throwable $e) {
            $state['status'] = 'failed';
            $state['error'] = $e->getMessage();
            Log::error('DB sync failed', ['table' => $state['plan'][$state['pos']] ?? null, 'error' => $e->getMessage()]);
        }

        $state['updated_at'] = now()->toIso8601String();
        Cache::put(self::STATE_KEY, $state, now()->addHours(6));
    }

    public function state(): ?array
    {
        return Cache::get(self::STATE_KEY);
    }

    public function clear(): void
    {
        Cache::forget(self::STATE_KEY);
    }

    /** Verify the target URL + secret before a real run (non-destructive). */
    public function ping(): array
    {
        $url = rtrim((string) config('datasync.target_url'), '/').'/api/db-sync/ping';

        try {
            $resp = Http::withHeaders(['X-Db-Sync-Secret' => (string) config('datasync.secret')])
                ->timeout(15)
                ->acceptJson()
                ->get($url);

            return [
                'ok' => $resp->successful(),
                'status' => $resp->status(),
                'body' => $resp->json() ?? $resp->body(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'body' => $e->getMessage()];
        }
    }

    private function finishTable(array $state, string $table, array $meta): array
    {
        $meta['status'] = 'done';
        $state['tables'][$table] = $meta;
        $state['pos']++;
        $state['sub'] = $this->freshSub();

        return $state;
    }

    private function fetchChunk(string $table, array $meta, array $sub, int $chunk)
    {
        $q = DB::table($table)->limit($chunk);

        if ($meta['pk']) {
            $q->orderBy($meta['pk']);
            if ($sub['last_id'] !== null) {
                $q->where($meta['pk'], '>', $sub['last_id']);
            }
        } else {
            foreach ($meta['order'] as $col) {
                $q->orderBy($col);
            }
            $q->offset($sub['offset']);
        }

        return $q->get();
    }

    private function send(string $table, string $mode, bool $truncate, array $rows): void
    {
        $url = rtrim((string) config('datasync.target_url'), '/').'/api/db-sync/push';

        $resp = Http::withHeaders(['X-Db-Sync-Secret' => (string) config('datasync.secret')])
            ->timeout((int) config('datasync.timeout', 120))
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->post($url, [
                'table' => $table,
                'mode' => $mode,
                'truncate' => $truncate,
                'rows' => $rows,
            ]);

        if (! $resp->successful()) {
            throw new \RuntimeException(
                "Push failed for [{$table}]: HTTP {$resp->status()} — ".mb_substr($resp->body(), 0, 300)
            );
        }
    }

    /** @return array<int, string> */
    private function primaryKeyColumns(string $table): array
    {
        $rows = DB::select(
            'select column_name as name from information_schema.columns
             where table_schema = database() and table_name = ? and column_key = ?
             order by ordinal_position',
            [$table, 'PRI']
        );

        return array_map(fn ($r) => $r->name, $rows);
    }

    /** @return array<int, string> */
    private function columns(string $table): array
    {
        $rows = DB::select(
            'select column_name as name from information_schema.columns
             where table_schema = database() and table_name = ?
             order by ordinal_position',
            [$table]
        );

        return array_map(fn ($r) => $r->name, $rows);
    }

    private function freshSub(): array
    {
        return ['last_id' => null, 'offset' => 0, 'truncated' => false];
    }

    private function receiver(): DbSyncReceiver
    {
        return app(DbSyncReceiver::class);
    }
}
