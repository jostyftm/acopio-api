<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Reusable trait for building filtered, sorted, and paginated queries
 * using spatie/laravel-query-builder.
 *
 * Usage in a Service class:
 *
 *   use Filterable;
 *
 *   public function index(User $user): Builder
 *   {
 *       $query = $this->buildFilteredQuery(Affectation::class, [
 *           'filters' => [
 *               AllowedFilter::partial('person.first_name'),
 *               AllowedFilter::exact('status_id'),
 *           ],
 *           'sorts' => ['id', 'created_at'],
 *           'defaultSort' => '-id',
 *       ]);
 *
 *       // Apply additional scopes...
 *       $query->with([...]);
 *
 *       return $query;
 *   }
 */
trait Filterable
{
    /**
     * Build a QueryBuilder with allowed filters, sorts, and includes.
     *
     * @param  class-string<Builder>  $model  Eloquent model class name.
     * @param  array{
     *     filters?: array<int, AllowedFilter>,
     *     sorts?: array<int, string>,
     *     includes?: array<int, string>,
     *     defaultSort?: string,
     * }  $config  Configuration for allowed filters, sorts, and includes.
     */
    public function buildFilteredQuery(string $model, array $config = []): QueryBuilder
    {
        $query = QueryBuilder::for($model);

        if (! empty($config['filters'])) {
            $query->allowedFilters($config['filters']);
        }

        if (! empty($config['sorts'])) {
            $query->allowedSorts($config['sorts']);
        }

        if (! empty($config['includes'])) {
            $query->allowedIncludes($config['includes']);
        }

        if (! empty($config['defaultSort'])) {
            $query->defaultSort($config['defaultSort']);
        }

        return $query;
    }
}
