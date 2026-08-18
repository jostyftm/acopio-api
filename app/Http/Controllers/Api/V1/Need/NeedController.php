<?php

namespace App\Http\Controllers\Api\V1\Need;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Need\StoreNeedRequest;
use App\Http\Resources\Api\V1\Need\NeedResource;
use App\Services\Need\NeedService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NeedController extends Controller
{
    public function __construct(
        private readonly NeedService $service,
    ) {}

    /**
     * Lista las necesidades activas del catálogo.
     *
     * Alimenta las casillas de necesidades del registro de afectación.
     */
    public function index(): JsonResponse
    {
        $needs = $this->service->index();

        return ApiResponse::success(NeedResource::collection($needs));
    }

    /**
     * Crea una nueva necesidad en el catálogo.
     *
     * Permite ampliar el catálogo conforme aparecen nuevas necesidades.
     * Requiere permiso `needs.manage` (administrador).
     */
    public function store(StoreNeedRequest $request): JsonResponse
    {
        $need = $this->service->create($request->validated());

        return ApiResponse::success(NeedResource::make($need), [], 201);
    }
}
