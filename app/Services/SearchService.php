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
     * Get SQL expression that normalizes a column by converting Nordic and accented characters to ASCII equivalents.
     *
     * @param string $colExpr
     * @param bool $isSqlite
     * @return string
     */
    public function getNormalizedSqlExpr(string $colExpr, bool $isSqlite): string
    {
        $expr = "LOWER({$colExpr})";

        $replacements = [
            'å' => 'a',
            'ä' => 'a',
            'ö' => 'o',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'æ' => 'ae',
            'ø' => 'o',
        ];

        if ($isSqlite) {
            $replacements['Å'] = 'a';
            $replacements['Ä'] = 'a';
            $replacements['Ö'] = 'o';
            $replacements['É'] = 'e';
            $replacements['È'] = 'e';
            $replacements['Ê'] = 'e';
            $replacements['Ë'] = 'e';
            $replacements['Æ'] = 'ae';
            $replacements['Ø'] = 'o';
        }

        foreach ($replacements as $from => $to) {
            $expr = "REPLACE({$expr}, '{$from}', '{$to}')";
        }

        return $expr;
    }

    /**
     * Build search query with Nordic character support, full-name concatenation, and multi-word matching.
     *
     * @param  Builder  $query
     * @param  string  $term
     * @param  array  $columns
     * @return Builder
     */
    public function buildSearchQuery(Builder $query, string $term, array $columns): Builder
    {
        $cleanTerm = preg_replace('/\s+/', ' ', trim($term));
        if (empty($cleanTerm)) {
            return $query;
        }

        $isSqlite = $this->isSqlite();
        $hasFirstName = in_array('first_name', $columns);
        $hasLastName = in_array('last_name', $columns);

        $concatFirstLast = $isSqlite ? "(first_name || ' ' || last_name)" : "CONCAT(first_name, ' ', last_name)";
        $concatLastFirst = $isSqlite ? "(last_name || ' ' || first_name)" : "CONCAT(last_name, ' ', first_name)";

        $words = array_values(array_filter(explode(' ', $cleanTerm)));
        $termLower = mb_strtolower($cleanTerm);
        $termNorm = mb_strtolower($this->normalizer->normalize($cleanTerm));

        return $query->where(function ($q) use (
            $columns,
            $cleanTerm,
            $words,
            $termLower,
            $termNorm,
            $isSqlite,
            $hasFirstName,
            $hasLastName,
            $concatFirstLast,
            $concatLastFirst
        ) {
            // 1. Full phrase match (matches individual columns or full concatenated names)
            $q->where(function ($subQ) use (
                $columns,
                $cleanTerm,
                $termLower,
                $termNorm,
                $isSqlite,
                $hasFirstName,
                $hasLastName,
                $concatFirstLast,
                $concatLastFirst
            ) {
                $isFirst = true;

                foreach ($columns as $column) {
                    $method = $isFirst ? 'where' : 'orWhere';
                    $isFirst = false;

                    if ($isSqlite) {
                        $subQ->$method(function ($innerQ) use ($column, $termLower) {
                            $innerQ->whereRaw("LOWER({$column}) LIKE ?", ['%' . $termLower . '%']);
                        });
                    } else {
                        $subQ->$method($column, 'like', '%' . $cleanTerm . '%');
                    }

                    // Normalized column match
                    $normCol = $this->getNormalizedSqlExpr($column, $isSqlite);
                    $subQ->orWhereRaw("{$normCol} LIKE ?", ['%' . $termNorm . '%']);
                }

                // If searching first_name and last_name, include full concatenated name matches
                if ($hasFirstName && $hasLastName) {
                    $subQ->orWhereRaw("LOWER({$concatFirstLast}) LIKE ?", ['%' . $termLower . '%'])
                         ->orWhereRaw("LOWER({$concatLastFirst}) LIKE ?", ['%' . $termLower . '%']);

                    $normConcatFL = $this->getNormalizedSqlExpr($concatFirstLast, $isSqlite);
                    $normConcatLF = $this->getNormalizedSqlExpr($concatLastFirst, $isSqlite);

                    $subQ->orWhereRaw("{$normConcatFL} LIKE ?", ['%' . $termNorm . '%'])
                         ->orWhereRaw("{$normConcatLF} LIKE ?", ['%' . $termNorm . '%']);
                }
            });

            // 2. Multi-word search: every word in the search term must match at least one column or full name
            if (count($words) > 1) {
                $q->orWhere(function ($multiWordQ) use (
                    $columns,
                    $words,
                    $isSqlite,
                    $hasFirstName,
                    $hasLastName,
                    $concatFirstLast
                ) {
                    foreach ($words as $word) {
                        $wordLower = mb_strtolower($word);
                        $wordNorm = mb_strtolower($this->normalizer->normalize($word));

                        $multiWordQ->where(function ($wordQ) use (
                            $columns,
                            $word,
                            $wordLower,
                            $wordNorm,
                            $isSqlite,
                            $hasFirstName,
                            $hasLastName,
                            $concatFirstLast
                        ) {
                            $isFirst = true;

                            foreach ($columns as $column) {
                                $method = $isFirst ? 'where' : 'orWhere';
                                $isFirst = false;

                                if ($isSqlite) {
                                    $wordQ->$method(function ($innerQ) use ($column, $wordLower) {
                                        $innerQ->whereRaw("LOWER({$column}) LIKE ?", ['%' . $wordLower . '%']);
                                    });
                                } else {
                                    $wordQ->$method($column, 'like', '%' . $word . '%');
                                }

                                $normCol = $this->getNormalizedSqlExpr($column, $isSqlite);
                                $wordQ->orWhereRaw("{$normCol} LIKE ?", ['%' . $wordNorm . '%']);
                            }

                            if ($hasFirstName && $hasLastName) {
                                $normConcatFL = $this->getNormalizedSqlExpr($concatFirstLast, $isSqlite);
                                $wordQ->orWhereRaw("{$normConcatFL} LIKE ?", ['%' . $wordNorm . '%']);
                            }
                        });
                    }
                });
            }
        });
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
