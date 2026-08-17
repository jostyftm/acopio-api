<?php

namespace App\Http\Controllers\Api\V1\IncidentType;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IncidentType\StoreIncidentTypeRequest;
use App\Http\Requests\Api\V1\IncidentType\UpdateIncidentTypeRequest;
use App\Http\Resources\Api\V1\IncidentType\IncidentTypeResource;
use App\Models\IncidentType;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IncidentTypeController extends Controller
{
    /**
     * Lista los tipos de incidente (derrumbes, incendios, etc.) con sus gravedades.
     *
     * @param  Request  $request  Consulta: `include_inactive=1` para incluir desactivados.
     */
    public function index(Request $request): JsonResponse
    {
        $query = IncidentType::query()->with('severities', 'needs')->orderBy('display_name');

        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        return ApiResponse::success(IncidentTypeResource::collection($query->get()));
    }

    /**
     * Crea un nuevo tipo de incidente.
     *
     * @param  StoreIncidentTypeRequest  $request  Datos del tipo.
     */
    public function store(StoreIncidentTypeRequest $request): JsonResponse
    {
        $type = IncidentType::query()->create($request->safe()->except('needs'));

        if ($request->has('needs')) {
            $type->needs()->sync($request->input('needs', []));
        }

        return ApiResponse::success(IncidentTypeResource::make($type->load('severities', 'needs')), null, 201);
    }

    /**
     * Muestra un tipo de incidente con sus gravedades.
     *
     * @param  IncidentType  $incidentType  El tipo de incidente.
     */
    public function show(IncidentType $incidentType): JsonResponse
    {
        return ApiResponse::success(IncidentTypeResource::make($incidentType->load('severities', 'needs')));
    }

    /**
     * Actualiza un tipo de incidente.
     *
     * @param  UpdateIncidentTypeRequest  $request  Datos a actualizar.
     * @param  IncidentType  $incidentType  El tipo a actualizar.
     */
    public function update(UpdateIncidentTypeRequest $request, IncidentType $incidentType): JsonResponse
    {
        $incidentType->update($request->safe()->except('needs'));

        if ($request->has('needs')) {
            $incidentType->needs()->sync($request->input('needs', []));
        }

        return ApiResponse::success(IncidentTypeResource::make($incidentType->load('severities', 'needs')));
    }

    /**
     * Elimina un tipo de incidente.
     *
     * @param  IncidentType  $incidentType  El tipo a eliminar.
     */
    public function destroy(IncidentType $incidentType): Response
    {
        if ($incidentType->affectations()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene afectaciones asignadas.', 409);
        }

        $incidentType->delete();

        return response()->noContent();
    }
}
