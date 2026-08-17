<?php

namespace App\Http\Controllers\Api\V1\CoverageZone;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CoverageZone\StoreCoverageZoneRequest;
use App\Http\Requests\Api\V1\CoverageZone\UpdateCoverageZoneRequest;
use App\Http\Resources\Api\V1\CoverageZone\CoverageZoneResource;
use App\Models\CoverageZone;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CoverageZoneController extends Controller
{
    /**
     * Lista las zonas de cobertura de forma paginada.
     *
     * @param  Request  $request  Consulta: filtros `name`, `municipality_id`, `organization_id` y `per_page`.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CoverageZone::class);

        $zones = CoverageZoneResource::collection(
            QueryBuilder::for(CoverageZone::class)
                ->with(['municipality.department', 'organizations'])
                ->allowedFilters(
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('municipality_id'),
                    AllowedFilter::exact('organization_id', 'organizations.id'),
                )
                ->defaultSort('name')
                ->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($zones);
    }

    /**
     * Crea una nueva zona de cobertura.
     */
    public function store(StoreCoverageZoneRequest $request): JsonResponse
    {
        $this->authorize('create', CoverageZone::class);

        $zone = CoverageZone::query()->create(
            $this->normalizePolygon($request->validated()),
        );

        return ApiResponse::success(
            CoverageZoneResource::make($zone->load('municipality.department')),
            null,
            201,
        );
    }

    /**
     * Actualiza una zona de cobertura.
     */
    public function update(UpdateCoverageZoneRequest $request, CoverageZone $coverageZone): JsonResponse
    {
        $this->authorize('update', $coverageZone);

        $coverageZone->update(
            $this->normalizePolygon($request->validated()),
        );

        return ApiResponse::success(
            CoverageZoneResource::make($coverageZone->load('municipality.department')),
        );
    }

    /**
     * Convierte el polígono recibido como array de [lon, lat] a WKT cerrado.
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

        // Cierra el anillo repitiendo el primer punto.
        if (count($points) > 0) {
            $points[] = $points[0];
        }

        $data['polygon'] = 'POLYGON(('.implode(', ', $points).'))';

        return $data;
    }

    /**
     * Elimina una zona de cobertura.
     */
    public function destroy(CoverageZone $coverageZone): Response
    {
        $this->authorize('delete', $coverageZone);

        $coverageZone->delete();

        return response()->noContent();
    }
}
