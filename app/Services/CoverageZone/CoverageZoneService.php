<?php

namespace App\Services\CoverageZone;

use App\Models\CoverageZone;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CoverageZoneService
{
    /**
     * Build the query for listing coverage zones with filters and pagination.
     *
     * @param  array<string, mixed>  $filters  Query parameters for filtering.
     */
    public function index(array $filters, int $perPage = 15): CursorPaginator
    {
        return QueryBuilder::for(CoverageZone::class)
            ->with(['municipality.department', 'organizations'])
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('municipality_id'),
                AllowedFilter::exact('organization_id', 'organizations.id'),
            )
            ->defaultSort('name')
            ->cursorPaginate($perPage);
    }

    /**
     * Create a new coverage zone, normalizing the polygon to WKT format.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CoverageZone
    {
        $data = $this->normalizePolygon($data);

        $zone = CoverageZone::query()->create($data);

        return $zone->load('municipality.department');
    }

    /**
     * Update an existing coverage zone, normalizing the polygon if provided.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CoverageZone $zone, array $data): CoverageZone
    {
        $data = $this->normalizePolygon($data);

        $zone->update($data);

        return $zone->load('municipality.department');
    }

    /**
     * Convert a polygon from an array of [lon, lat] coordinates to WKT format.
     *
     * The ring is automatically closed by repeating the first point.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePolygon(array $data): array
    {
        if (! isset($data['polygon']) || ! is_array($data['polygon'])) {
            return $data;
        }

        $points = array_map(
            fn (array $point): string => sprintf('%s %s', $point[0], $point[1]),
            $data['polygon'],
        );

        if (count($points) > 0) {
            $points[] = $points[0];
        }

        $data['polygon'] = 'POLYGON(('.implode(', ', $points).'))';

        return $data;
    }
}
