<?php

namespace App\Http\Controllers\Api\V1\Visor;

use App\Http\Controllers\Controller;
use App\Services\Visor\VisorService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class VisorController extends Controller
{
    public function __construct(
        private readonly VisorService $visorService,
    ) {}

    /**
     * Devuelve la instantánea situacional pública del visor geográfico.
     *
     * KPIs agregados, agregados por municipio y una muestra de puntos
     * recientes anonimizados (sin datos personales).
     */
    public function show(): JsonResponse
    {
        return ApiResponse::success($this->visorService->snapshot());
    }
}
