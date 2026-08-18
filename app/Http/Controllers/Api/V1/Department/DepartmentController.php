<?php

namespace App\Http\Controllers\Api\V1\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Department\IndexDepartmentRequest;
use App\Http\Resources\Api\V1\Department\DepartmentResource;
use App\Services\Department\DepartmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $service,
    ) {}

    /**
     * Lista los departamentos del catálogo DIVIPOLA.
     *
     * Alimenta el selector de departamento del registro y la búsqueda pública.
     */
    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        $departments = $this->service->index(
            $request->only(['term']),
        );

        return ApiResponse::success(DepartmentResource::collection($departments));
    }
}
