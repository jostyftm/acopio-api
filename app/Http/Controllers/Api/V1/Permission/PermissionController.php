<?php

namespace App\Http\Controllers\Api\V1\Permission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Permission\StorePermissionRequest;
use App\Http\Requests\Api\V1\Permission\UpdatePermissionRequest;
use App\Http\Resources\Api\V1\Module\PermissionResource;
use App\Models\Permission;
use App\Services\Permission\PermissionService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    /**
     * Lista los permisos del sistema de forma paginada.
     *
     * Permite filtrar por módulo y por nombre.
     *
     * @param  Request  $request  Consulta: filtros `module_id`, `name` y `per_page`.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = PermissionResource::collection(
            QueryBuilder::for(Permission::class)
                ->with('module')
                ->allowedFilters(
                    AllowedFilter::exact('module_id'),
                    AllowedFilter::partial('name'),
                )
                ->defaultSort('name')
                ->cursorPaginate($request->integer('per_page', 100)),
        );

        return ApiResponse::success($permissions);
    }

    /**
     * Crea un nuevo permiso vinculado a un módulo.
     *
     * El nombre se compone como `{modulo}.{accion}`.
     *
     * @param  StorePermissionRequest  $request  Datos del permiso.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $permission = $this->permissionService->create($request->validated());

        return ApiResponse::success(
            PermissionResource::make($permission->load('module')),
            null,
            201,
        );
    }

    /**
     * Muestra el detalle de un permiso.
     *
     * @param  Permission  $permission  El permiso a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        return ApiResponse::success(PermissionResource::make($permission->load('module')));
    }

    /**
     * Actualiza un permiso.
     *
     * @param  UpdatePermissionRequest  $request  Datos a actualizar.
     * @param  Permission  $permission  El permiso a actualizar.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $this->authorize('update', $permission);

        $permission = $this->permissionService->update($permission, $request->validated());

        return ApiResponse::success(PermissionResource::make($permission));
    }

    /**
     * Elimina un permiso.
     *
     * @param  Permission  $permission  El permiso a eliminar.
     */
    public function destroy(Permission $permission): Response
    {
        $this->authorize('delete', $permission);

        $this->permissionService->destroy($permission);

        return response()->noContent();
    }
}
