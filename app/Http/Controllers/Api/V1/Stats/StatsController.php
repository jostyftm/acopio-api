<?php

namespace App\Http\Controllers\Api\V1\Stats;

use App\Http\Controllers\Controller;
use App\Services\Stats\StatsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(
        private readonly StatsService $statsService,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success($this->statsService->summary());
    }
}
