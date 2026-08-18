<?php

namespace App\Http\Controllers\Api\V1\CoverageZone;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CoverageZone\StoreCoverageZoneRequest;
use App\Http\Requests\Api\V1\CoverageZone\UpdateCoverageZoneRequest;
use App\Http\Resources\Api\V1\CoverageZone\CoverageZoneResource;
use App\Models\CoverageZone;
use App\Services\CoverageZone\CoverageZoneService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoverageZoneController extends Controller
{
    public function __construct(
        private readonly CoverageZoneService $service,
    ) {}

    /**
     * Lista las zonas de cobertura de forma paginada.
     *
     * @param  Request  $request  Consulta: filtros `name`, `municipality_id`, `organization_id` y `per_page`.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CoverageZone::class);

        $zones = CoverageZoneResource::collection(
            $this->service->index(
                $request->only(['name', 'municipality_id', 'organization_id']),
                $request->integer('per_page', 15),
            ),
        );

        return ApiResponse::success($zones);
    }

    /**
     * Crea una nueva zona de cobertura.
     */
    public function store(StoreCoverageZoneRequest $request): JsonResponse
    {
        $this->authorize('create', CoverageZone::class);

        $zone = $this->service->create($request->validated());

        return ApiResponse::success(
            CoverageZoneResource::make($zone),
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

        $zone = $this->service->update($coverageZone, $request->validated());

        return ApiResponse::success(
            CoverageZoneResource::make($zone),
        );
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
