<?php

namespace App\Http\Controllers\Api\V1\IncidentType;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IncidentType\StoreIncidentTypeRequest;
use App\Http\Requests\Api\V1\IncidentType\UpdateIncidentTypeRequest;
use App\Http\Resources\Api\V1\IncidentType\IncidentTypeResource;
use App\Models\IncidentType;
use App\Services\IncidentType\IncidentTypeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IncidentTypeController extends Controller
{
    public function __construct(
        private readonly IncidentTypeService $service,
    ) {}

    /**
     * Lista los tipos de incidente (derrumbes, incendios, etc.) con sus gravedades.
     *
     * @param  Request  $request  Consulta: `include_inactive=1` para incluir desactivados.
     */
    public function index(Request $request): JsonResponse
    {
        $types = $this->service->index($request->boolean('include_inactive'));

        return ApiResponse::success(IncidentTypeResource::collection($types));
    }

    /**
     * Crea un nuevo tipo de incidente.
     *
     * @param  StoreIncidentTypeRequest  $request  Datos del tipo.
     */
    public function store(StoreIncidentTypeRequest $request): JsonResponse
    {
        $type = $this->service->create($request->validated());

        return ApiResponse::success(IncidentTypeResource::make($type), null, 201);
    }

    /**
     * Muestra un tipo de incidente con sus gravedades.
     *
     * @param  IncidentType  $incidentType  El tipo de incidente.
     */
    public function show(IncidentType $incidentType): JsonResponse
    {
        $incidentType = $this->service->loadRelations($incidentType);

        return ApiResponse::success(IncidentTypeResource::make($incidentType));
    }

    /**
     * Actualiza un tipo de incidente.
     *
     * @param  UpdateIncidentTypeRequest  $request  Datos a actualizar.
     * @param  IncidentType  $incidentType  El tipo a actualizar.
     */
    public function update(UpdateIncidentTypeRequest $request, IncidentType $incidentType): JsonResponse
    {
        $type = $this->service->update($incidentType, $request->validated());

        return ApiResponse::success(IncidentTypeResource::make($type));
    }

    /**
     * Elimina un tipo de incidente.
     *
     * @param  IncidentType  $incidentType  El tipo a eliminar.
     */
    public function destroy(IncidentType $incidentType): Response
    {
        $this->service->delete($incidentType);

        return response()->noContent();
    }
}
