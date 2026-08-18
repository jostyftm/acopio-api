<?php

namespace App\Http\Controllers\Api\V1\Municipality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Municipality\IndexMunicipalityRequest;
use App\Http\Resources\Api\V1\Municipality\MunicipalityResource;
use App\Services\Municipality\MunicipalityService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MunicipalityController extends Controller
{
    public function __construct(
        private readonly MunicipalityService $service,
    ) {}

    /**
     * Lista los municipios del catálogo DIVIPOLA.
     *
     * Filtra por término normalizado o departamento para alimentar los
     * campos de municipio del registro y la búsqueda pública.
     */
    public function index(IndexMunicipalityRequest $request): JsonResponse
    {
        $municipalities = $this->service->index(
            $request->only(['term', 'department', 'limit']),
        );

        return ApiResponse::success(MunicipalityResource::collection($municipalities));
    }
}
