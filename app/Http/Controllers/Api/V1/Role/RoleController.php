<?php

namespace App\Http\Controllers\Api\V1\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Role\StoreRoleRequest;
use App\Http\Requests\Api\V1\Role\UpdateRoleRequest;
use App\Http\Resources\Api\V1\Role\RoleResource;
use App\Models\Role;
use App\Services\Role\RoleService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    /**
     * Lista los roles del sistema de forma paginada.
     *
     * Devuelve los roles con sus permisos asociados.
     *
     * @param  Request  $request  Consulta: filtro `name` y `per_page`.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = RoleResource::collection(
            QueryBuilder::for(Role::class)
                ->with('permissions')
                ->allowedFilters(AllowedFilter::partial('name'))
                ->defaultSort('name')
                ->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($roles);
    }

    /**
     * Crea un nuevo rol con sus permisos.
     *
     * @param  StoreRoleRequest  $request  Datos del rol.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->create($request->validated());

        return ApiResponse::success(RoleResource::make($role), null, 201);
    }

    /**
     * Muestra el detalle de un rol.
     *
     * @param  Role  $role  El rol a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return ApiResponse::success(RoleResource::make($role->load('permissions')));
    }

    /**
     * Actualiza un rol y sincroniza sus permisos.
     *
     * @param  UpdateRoleRequest  $request  Datos a actualizar.
     * @param  Role  $role  El rol a actualizar.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $role = $this->roleService->update($role, $request->validated());

        return ApiResponse::success(RoleResource::make($role));
    }

    /**
     * Elimina un rol siempre que no esté asignado a usuarios.
     *
     * @param  Role  $role  El rol a eliminar.
     *
     * @throws AuthorizationException
     */
    public function destroy(Role $role): Response
    {
        $this->authorize('delete', $role);

        $this->roleService->destroy($role);

        return response()->noContent();
    }
}
