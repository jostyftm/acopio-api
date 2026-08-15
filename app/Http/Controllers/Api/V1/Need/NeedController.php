<?php

namespace App\Http\Controllers\Api\V1\Need;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Need\StoreNeedRequest;
use App\Http\Resources\Api\V1\Need\NeedResource;
use App\Models\Need;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NeedController extends Controller
{
    /**
     * Lista las necesidades activas del catálogo.
     *
     * Alimenta las casillas de necesidades del registro de afectación.
     */
    public function index(): JsonResponse
    {
        $needs = Need::query()->active()->with('severityNeed')->orderBy('name')->get();

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
        $need = Need::query()->create([
            'name' => $request->string('name'),
            'normalized_name' => Need::normalizeName((string) $request->string('name')),
            'description' => $request->input('description'),
            'severity_need_id' => $request->integer('severity_need_id'),
        ]);

        return ApiResponse::success(NeedResource::make($need), [], 201);
    }
}
