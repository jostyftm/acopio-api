<?php

namespace App\Http\Controllers\Api\V1\OrganizationType;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrganizationType\StoreOrganizationTypeRequest;
use App\Http\Requests\Api\V1\OrganizationType\UpdateOrganizationTypeRequest;
use App\Http\Resources\Api\V1\OrganizationType\OrganizationTypeResource;
use App\Models\OrganizationType;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrganizationTypeController extends Controller
{
    /**
     * Lista los tipos de organización.
     *
     * @param  Request  $request  Consulta: `include_inactive=1` para incluir desactivados.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrganizationType::query()->orderBy('display_name');

        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        return ApiResponse::success(OrganizationTypeResource::collection($query->get()));
    }

    /**
     * Crea un nuevo tipo de organización.
     *
     * @param  StoreOrganizationTypeRequest  $request  Datos del tipo.
     */
    public function store(StoreOrganizationTypeRequest $request): JsonResponse
    {
        $type = OrganizationType::query()->create($request->validated());

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
        $type->update($request->validated());

        return ApiResponse::success(OrganizationTypeResource::make($type));
    }

    /**
     * Elimina un tipo de organización.
     *
     * @param  OrganizationType  $type  El tipo a eliminar.
     */
    public function destroy(OrganizationType $type): Response
    {
        if ($type->organizations()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene organizaciones asignadas.', 409);
        }

        $type->delete();

        return response()->noContent();
    }
}
