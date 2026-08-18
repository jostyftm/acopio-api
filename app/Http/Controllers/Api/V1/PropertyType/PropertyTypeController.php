<?php

namespace App\Http\Controllers\Api\V1\PropertyType;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PropertyType\StorePropertyTypeRequest;
use App\Http\Requests\Api\V1\PropertyType\UpdatePropertyTypeRequest;
use App\Http\Resources\Api\V1\PropertyType\PropertyTypeResource;
use App\Models\PropertyType;
use App\Services\PropertyType\PropertyTypeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PropertyTypeController extends Controller
{
    public function __construct(
        private readonly PropertyTypeService $service,
    ) {}

    /**
     * Lista los tipos de predio (vivienda, negocio, hospital, etc.).
     *
     * @param  Request  $request  Consulta: `include_inactive=1` para incluir desactivados.
     */
    public function index(Request $request): JsonResponse
    {
        $types = $this->service->index($request->boolean('include_inactive'));

        return ApiResponse::success(PropertyTypeResource::collection($types));
    }

    /**
     * Crea un nuevo tipo de predio.
     *
     * @param  StorePropertyTypeRequest  $request  Datos del tipo.
     */
    public function store(StorePropertyTypeRequest $request): JsonResponse
    {
        $type = $this->service->create($request->validated());

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
        $type = $this->service->update($propertyType, $request->validated());

        return ApiResponse::success(PropertyTypeResource::make($type));
    }

    /**
     * Elimina un tipo de predio.
     *
     * @param  PropertyType  $propertyType  El tipo a eliminar.
     */
    public function destroy(PropertyType $propertyType): Response
    {
        $this->service->delete($propertyType);

        return response()->noContent();
    }
}
