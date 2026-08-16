<?php

namespace App\Http\Controllers\Api\V1\FacilityType;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FacilityType\StoreFacilityTypeRequest;
use App\Http\Requests\Api\V1\FacilityType\UpdateFacilityTypeRequest;
use App\Http\Resources\Api\V1\FacilityType\FacilityTypeResource;
use App\Models\FacilityType;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacilityTypeController extends Controller
{
    /**
     * Lista los tipos de espacio (acopio, albergue, etc.).
     *
     * @param  Request  $request  Consulta: `include_inactive=1` para incluir desactivados.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FacilityType::query()->orderBy('display_name');

        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        return ApiResponse::success(FacilityTypeResource::collection($query->get()));
    }

    /**
     * Crea un nuevo tipo de espacio.
     *
     * @param  StoreFacilityTypeRequest  $request  Datos del tipo.
     */
    public function store(StoreFacilityTypeRequest $request): JsonResponse
    {
        $type = FacilityType::query()->create($request->validated());

        return ApiResponse::success(FacilityTypeResource::make($type), null, 201);
    }

    /**
     * Actualiza un tipo de espacio.
     *
     * @param  UpdateFacilityTypeRequest  $request  Datos a actualizar.
     * @param  FacilityType  $type  El tipo a actualizar.
     */
    public function update(UpdateFacilityTypeRequest $request, FacilityType $type): JsonResponse
    {
        $type->update($request->validated());

        return ApiResponse::success(FacilityTypeResource::make($type));
    }

    /**
     * Elimina un tipo de espacio.
     *
     * @param  FacilityType  $type  El tipo a eliminar.
     */
    public function destroy(FacilityType $type): Response
    {
        if ($type->facilities()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene espacios asignados.', 409);
        }

        $type->delete();

        return response()->noContent();
    }
}
