<?php

namespace App\Http\Controllers\Api\V1\Module;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Module\StoreModuleRequest;
use App\Http\Requests\Api\V1\Module\UpdateModuleRequest;
use App\Http\Resources\Api\V1\Module\ModuleResource;
use App\Models\Module;
use App\Services\Module\ModuleService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ModuleController extends Controller
{
    public function __construct(
        private readonly ModuleService $moduleService,
    ) {}

    /**
     * Lista los módulos del sistema como árbol.
     *
     * Devuelve los módulos de primer nivel con sus hijos y permisos asociados,
     * ordenados según el campo `order`.
     *
     * @throws AuthorizationException
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Module::class);

        $modules = Module::query()
            ->with([
                'permissions',
                'children' => fn ($query) => $query->orderBy('order'),
                'children.permissions',
                'children.children' => fn ($query) => $query->orderBy('order'),
            ])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return ApiResponse::success(ModuleResource::collection($modules));
    }

    /**
     * Crea un nuevo módulo.
     *
     * @param  StoreModuleRequest  $request  Datos del módulo.
     */
    public function store(StoreModuleRequest $request): JsonResponse
    {
        $this->authorize('create', Module::class);

        $module = $this->moduleService->create($request->validated());

        return ApiResponse::success(ModuleResource::make($module), null, 201);
    }

    /**
     * Muestra el detalle de un módulo.
     *
     * @param  Module  $module  El módulo a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(Module $module): JsonResponse
    {
        $this->authorize('view', $module);

        return ApiResponse::success(ModuleResource::make($module->load(['permissions', 'children'])));
    }

    /**
     * Actualiza un módulo.
     *
     * @param  UpdateModuleRequest  $request  Datos a actualizar.
     * @param  Module  $module  El módulo a actualizar.
     */
    public function update(UpdateModuleRequest $request, Module $module): JsonResponse
    {
        $this->authorize('update', $module);

        $module = $this->moduleService->update($module, $request->validated());

        return ApiResponse::success(ModuleResource::make($module->load('permissions')));
    }

    /**
     * Elimina un módulo.
     *
     * @param  Module  $module  El módulo a eliminar.
     */
    public function destroy(Module $module): Response
    {
        $this->authorize('delete', $module);

        $this->moduleService->destroy($module);

        return response()->noContent();
    }
}
