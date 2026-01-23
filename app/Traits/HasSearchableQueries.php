<?php

namespace App\Traits;

use App\Services\SearchService;
use Illuminate\Database\Eloquent\Builder;

trait HasSearchableQueries
{
    /**
     * Apply search to a query with Nordic character support
     *
     * @param  Builder  $query
     * @param  string  $searchTerm
     * @param  array  $columns
     * @return Builder
     */
    protected function applySearch(Builder $query, string $searchTerm, array $columns): Builder
    {
        if (empty(trim($searchTerm))) {
            return $query;
        }

        $searchService = app(SearchService::class);

        return $searchService->buildSearchQuery($query, $searchTerm, $columns);
    }

    /**
     * Apply search to a relationship
     *
     * @param  Builder  $query
     * @param  string  $searchTerm
     * @param  string  $relation
     * @param  array  $columns
     * @return Builder
     */
    protected function applySearchToRelation(Builder $query, string $searchTerm, string $relation, array $columns): Builder
    {
        if (empty(trim($searchTerm))) {
            return $query;
        }

        $searchService = app(SearchService::class);

        return $searchService->buildRelationSearchQuery($query, $searchTerm, $relation, $columns);
    }

    /**
     * Apply search to multiple relationships
     *
     * @param  Builder  $query
     * @param  string  $searchTerm
     * @param  array  $relations
     * @return Builder
     */
    protected function applySearchToMultipleRelations(Builder $query, string $searchTerm, array $relations): Builder
    {
        if (empty(trim($searchTerm))) {
            return $query;
        }

        $searchService = app(SearchService::class);

        return $searchService->buildMultiRelationSearchQuery($query, $searchTerm, $relations);
    }
}
