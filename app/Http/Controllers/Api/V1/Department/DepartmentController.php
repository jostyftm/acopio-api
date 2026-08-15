<?php

namespace App\Http\Controllers\Api\V1\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Department\IndexDepartmentRequest;
use App\Http\Resources\Api\V1\Department\DepartmentResource;
use App\Models\Department;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * Lista los departamentos del catálogo DIVIPOLA.
     *
     * Alimenta el selector de departamento del registro y la búsqueda pública.
     */
    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        $departments = Department::query()
            ->when($request->filled('term'), function ($query) use ($request): void {
                $query->where('normalized_name', 'ilike', '%'.$request->string('term').'%');
            })
            ->orderBy('name')
            ->get();

        return ApiResponse::success(DepartmentResource::collection($departments));
    }
}
