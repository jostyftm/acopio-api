<?php

namespace App\Http\Controllers\Api\V1\Balance;

use App\Http\Controllers\Controller;
use App\Services\Balance\BalanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    public function __construct(
        private readonly BalanceService $balanceService,
    ) {}

    /**
     * Devuelve el balance general preliminar de afectaciones.
     *
     * Métricas agregadas: familias y viviendas afectadas, viviendas
     * destruidas/averiadas, fallecidos (total y por causa) y lesionados.
     */
    public function show(): JsonResponse
    {
        return ApiResponse::success($this->balanceService->summary());
    }
}
