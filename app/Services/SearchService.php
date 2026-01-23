<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Character normalizer instance
     *
     * @var CharacterNormalizer
     */
    protected CharacterNormalizer $normalizer;

    /**
     * Create a new SearchService instance
     */
    public function __construct(CharacterNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Build search query with Nordic character support and case-insensitivity
     *
     * @param  Builder  $query
     * @param  string  $term
     * @param  array  $columns
     * @return Builder
     */
    public function buildSearchQuery(Builder $query, string $term, array $columns): Builder
    {
        $originalTerm = trim($term);
        $normalizedTerm = $this->normalizer->normalize($originalTerm);

        $isSqlite = $this->isSqlite();

        return $query->where(function ($q) use ($columns, $originalTerm, $normalizedTerm, $isSqlite) {
            $isFirst = true;

            foreach ($columns as $column) {
                // Search with original term
                $this->addSearchCondition($q, $column, $originalTerm, $isSqlite, $isFirst);
                $isFirst = false;

                // If normalized is different, search with normalized term too
                if ($normalizedTerm !== $originalTerm) {
                    $this->addSearchCondition($q, $column, $normalizedTerm, $isSqlite, false);
                }
            }
        });
    }

    /**
     * Add a search condition to the query
     *
     * @param  Builder  $query
     * @param  string  $column
     * @param  string  $term
     * @param  bool  $isSqlite
     * @param  bool  $isFirst
     * @return void
     */
    protected function addSearchCondition(Builder $query, string $column, string $term, bool $isSqlite, bool $isFirst): void
    {
        $method = $isFirst ? 'where' : 'orWhere';

        if ($isSqlite) {
            // SQLite: Use LOWER() for case-insensitive search
            $query->$method(function ($q) use ($column, $term) {
                $q->whereRaw("LOWER({$column}) LIKE ?", ['%' . strtolower($term) . '%']);
            });
        } else {
            // MySQL/MariaDB: Collation handles case-insensitivity
            $query->$method($column, 'like', '%' . $term . '%');
        }
    }

    /**
     * Check if the current database connection is SQLite
     *
     * @return bool
     */
    protected function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Build search query for relationships
     *
     * @param  Builder  $query
     * @param  string  $term
     * @param  string  $relation
     * @param  array  $columns
     * @return Builder
     */
    public function buildRelationSearchQuery(Builder $query, string $term, string $relation, array $columns): Builder
    {
        return $query->whereHas($relation, function ($q) use ($term, $columns) {
            $this->buildSearchQuery($q, $term, $columns);
        });
    }

    /**
     * Build search query for multiple relationships
     *
     * @param  Builder  $query
     * @param  string  $term
     * @param  array  $relations
     * @return Builder
     */
    public function buildMultiRelationSearchQuery(Builder $query, string $term, array $relations): Builder
    {
        return $query->where(function ($q) use ($term, $relations) {
            foreach ($relations as $relation => $columns) {
                $q->orWhereHas($relation, function ($subQ) use ($term, $columns) {
                    $this->buildSearchQuery($subQ, $term, $columns);
                });
            }
        });
    }
}
