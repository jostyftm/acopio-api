<?php

namespace App\Http\Controllers\Api\V1\Municipality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Municipality\IndexMunicipalityRequest;
use App\Http\Resources\Api\V1\Municipality\MunicipalityResource;
use App\Models\Department;
use App\Models\Municipality;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MunicipalityController extends Controller
{
    /**
     * Lista los municipios del catálogo DIVIPOLA.
     *
     * Filtra por término normalizado o departamento para alimentar los
     * campos de municipio del registro y la búsqueda pública.
     */
    public function index(IndexMunicipalityRequest $request): JsonResponse
    {
        $municipalities = Municipality::query()
            ->with('department')
            ->when($request->filled('term'), function ($query) use ($request): void {
                $query->where('normalized_name', 'ilike', '%'.$request->string('term').'%');
            })
            ->when($request->filled('department'), function ($query) use ($request): void {
                $query->whereHas('department', function ($query) use ($request): void {
                    $query->where('name', 'ilike', $request->string('department'));
                });
            })
            ->orderBy(Department::select('code')->whereColumn('departments.id', 'municipalities.department_id'))
            ->orderBy('name')
            ->limit($request->integer('limit', 500))
            ->get();

        return ApiResponse::success(MunicipalityResource::collection($municipalities));
    }
}
