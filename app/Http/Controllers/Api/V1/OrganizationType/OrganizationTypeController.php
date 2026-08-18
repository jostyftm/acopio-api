<?php

namespace App\Http\Controllers\Api\V1\OrganizationType;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrganizationType\StoreOrganizationTypeRequest;
use App\Http\Requests\Api\V1\OrganizationType\UpdateOrganizationTypeRequest;
use App\Http\Resources\Api\V1\OrganizationType\OrganizationTypeResource;
use App\Models\OrganizationType;
use App\Services\OrganizationType\OrganizationTypeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrganizationTypeController extends Controller
{
    public function __construct(
        private readonly OrganizationTypeService $service,
    ) {}

    /**
     * Lista los tipos de organización.
     *
     * @param  Request  $request  Consulta: `include_inactive=1` para incluir desactivados.
     */
    public function index(Request $request): JsonResponse
    {
        $types = $this->service->index($request->boolean('include_inactive'));

        return ApiResponse::success(OrganizationTypeResource::collection($types));
    }

    /**
     * Crea un nuevo tipo de organización.
     *
     * @param  StoreOrganizationTypeRequest  $request  Datos del tipo.
     */
    public function store(StoreOrganizationTypeRequest $request): JsonResponse
    {
        $type = $this->service->create($request->validated());

        return ApiResponse::success(OrganizationTypeResource::make($type), null, 201);
    }

    /**
     * Actualiza un tipo de organización.
     *
     * @param  UpdateOrganizationTypeRequest  $request  Datos a actualizar.
     * @param  OrganizationType  $type  El tipo a actualizar.
     */
    public function update(UpdateOrganizationTypeRequest $request, OrganizationType $type): JsonResponse
    {
        $type = $this->service->update($type, $request->validated());

        return ApiResponse::success(OrganizationTypeResource::make($type));
    }

    /**
     * Elimina un tipo de organización.
     *
     * @param  OrganizationType  $type  El tipo a eliminar.
     */
    public function destroy(OrganizationType $type): Response
    {
        $this->service->delete($type);

        return response()->noContent();
    }
}
