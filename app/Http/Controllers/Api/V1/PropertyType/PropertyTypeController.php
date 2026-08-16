<?php

namespace App\Http\Controllers\Api\V1\PropertyType;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PropertyType\StorePropertyTypeRequest;
use App\Http\Requests\Api\V1\PropertyType\UpdatePropertyTypeRequest;
use App\Http\Resources\Api\V1\PropertyType\PropertyTypeResource;
use App\Models\PropertyType;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PropertyTypeController extends Controller
{
    /**
     * Lista los tipos de predio (vivienda, negocio, hospital, etc.).
     *
     * @param  Request  $request  Consulta: `include_inactive=1` para incluir desactivados.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PropertyType::query()->orderBy('display_name');

        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        return ApiResponse::success(PropertyTypeResource::collection($query->get()));
    }

    /**
     * Crea un nuevo tipo de predio.
     *
     * @param  StorePropertyTypeRequest  $request  Datos del tipo.
     */
    public function store(StorePropertyTypeRequest $request): JsonResponse
    {
        $type = PropertyType::query()->create($request->validated());

        return ApiResponse::success(PropertyTypeResource::make($type), null, 201);
    }

    /**
     * Actualiza un tipo de predio.
     *
     * @param  UpdatePropertyTypeRequest  $request  Datos a actualizar.
     * @param  PropertyType  $propertyType  El tipo a actualizar.
     */
    public function update(UpdatePropertyTypeRequest $request, PropertyType $propertyType): JsonResponse
    {
        $propertyType->update($request->validated());

        return ApiResponse::success(PropertyTypeResource::make($propertyType));
    }

    /**
     * Elimina un tipo de predio.
     *
     * @param  PropertyType  $propertyType  El tipo a eliminar.
     */
    public function destroy(PropertyType $propertyType): Response
    {
        if ($propertyType->affectations()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene afectaciones asignadas.', 409);
        }

        $propertyType->delete();

        return response()->noContent();
    }
}
