<?php

namespace App\Services\DbSync;

use Illuminate\Support\Facades\DB;

/**
 * Receiver side (runs on production).
 *
 * Applies one incoming batch to a table, writing rows EXACTLY as received —
 * primary keys and every foreign key are preserved, so IDs and ownership are
 * never changed. Foreign-key checks are disabled during the write so rows can
 * be inserted in any order / a referenced table can be cleared safely.
 */
class DbSyncReceiver
{
    /**
     * @param  string  $table     Target table name.
     * @param  string  $mode      replace | merge | insert.
     * @param  bool    $truncate  True on the first batch of a table (replace mode clears it).
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{table: string, mode: string, cleared: bool, affected: int}
     */
    public function apply(string $table, string $mode, bool $truncate, array $rows): array
    {
        if (! $this->isSyncable($table)) {
            abort(422, "Table [{$table}] is not syncable on this instance.");
        }

        if (! in_array($mode, ['replace', 'merge', 'insert'], true)) {
            abort(422, "Unknown sync mode [{$mode}].");
        }

        $affected = 0;
        $cleared = false;

        DB::transaction(function () use ($table, $mode, $truncate, $rows, &$affected, &$cleared) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Exact-mirror: wipe the whole table once, on its first batch.
            // DELETE (not TRUNCATE) so FK-referenced tables can be cleared while
            // other tables still hold rows.
            if ($mode === 'replace' && $truncate) {
                DB::table($table)->delete();
                $cleared = true;
            }

            $pk = $this->primaryKeyColumns($table);

            foreach (array_chunk($rows, 200) as $group) {
                match ($mode) {
                    // Only add rows that aren't already there.
                    'insert' => DB::table($table)->insertOrIgnore($group),
                    // Overwrite matching-id rows, insert the rest, keep others.
                    'merge' => $pk
                        ? DB::table($table)->upsert($group, $pk)
                        : DB::table($table)->insertOrIgnore($group),
                    // replace: table was cleared on its first batch. Upsert (not a
                    // plain insert) so that if the HTTP client re-sends a batch the
                    // server already committed (see DbSyncPusher::send's retry), the
                    // resend overwrites those ids with the exact same local rows
                    // instead of throwing a duplicate-key error. Still an exact
                    // mirror — just idempotent under retries.
                    default => $pk
                        ? DB::table($table)->upsert($group, $pk)
                        : DB::table($table)->insert($group),
                };

                $affected += count($group);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        return [
            'table' => $table,
            'mode' => $mode,
            'cleared' => $cleared,
            'affected' => $affected,
        ];
    }

    /** A table is syncable if it really exists and isn't a framework/runtime table. */
    public function isSyncable(string $table): bool
    {
        if (in_array($table, (array) config('datasync.ignored_tables'), true)) {
            return false;
        }

        return in_array($table, $this->realTables(), true);
    }

    /** @return array<int, string> */
    public function realTables(): array
    {
        $rows = DB::select(
            'select table_name as name from information_schema.tables where table_schema = database()'
        );

        return array_map(fn ($r) => $r->name, $rows);
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
}
