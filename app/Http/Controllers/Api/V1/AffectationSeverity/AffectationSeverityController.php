<?php

namespace App\Http\Controllers\Api\V1\AffectationSeverity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AffectationSeverity\StoreAffectationSeverityRequest;
use App\Http\Requests\Api\V1\AffectationSeverity\UpdateAffectationSeverityRequest;
use App\Http\Resources\Api\V1\AffectationSeverity\AffectationSeverityResource;
use App\Models\AffectationSeverity;
use App\Services\AffectationSeverity\AffectationSeverityService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AffectationSeverityController extends Controller
{
    public function __construct(
        private readonly AffectationSeverityService $service,
    ) {}

    /**
     * Crea una gravedad para un tipo de incidente.
     *
     * @param  StoreAffectationSeverityRequest  $request  Datos de la gravedad.
     */
    public function store(StoreAffectationSeverityRequest $request): JsonResponse
    {
        $severity = $this->service->create($request->validated());

        return ApiResponse::success(AffectationSeverityResource::make($severity), null, 201);
    }

    /**
     * Muestra una gravedad de afectación.
     *
     * @param  AffectationSeverity  $affectationSeverity  La gravedad.
     */
    public function show(AffectationSeverity $affectationSeverity): JsonResponse
    {
        return ApiResponse::success(AffectationSeverityResource::make($affectationSeverity->load('incidentType')));
    }

    /**
     * Actualiza una gravedad de afectación.
     *
     * @param  UpdateAffectationSeverityRequest  $request  Datos a actualizar.
     * @param  AffectationSeverity  $affectationSeverity  La gravedad a actualizar.
     */
    public function update(UpdateAffectationSeverityRequest $request, AffectationSeverity $affectationSeverity): JsonResponse
    {
        $severity = $this->service->update($affectationSeverity, $request->validated());

        return ApiResponse::success(AffectationSeverityResource::make($severity));
    }

    /**
     * Elimina una gravedad de afectación.
     *
     * @param  AffectationSeverity  $affectationSeverity  La gravedad a eliminar.
     */
    public function destroy(AffectationSeverity $affectationSeverity): Response
    {
        $this->service->delete($affectationSeverity);

        return response()->noContent();
    }
}
