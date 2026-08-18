<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Lista los usuarios del sistema de forma paginada.
     *
     * Devuelve los usuarios registrados con sus roles. Permite filtrar por
     * email y ordenar por fecha de creación.
     *
     * @param  Request  $request  Consulta: filtro `email` y `per_page`.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = UserResource::collection(
            $this->userService->index(
                $request->user(),
                $request->only(['email']),
                $request->integer('per_page', 15),
            ),
        );

        return ApiResponse::success($users);
    }

    /**
     * Crea un nuevo usuario.
     *
     * Registra un usuario en el sistema con sus datos de acceso.
     *
     * @param  StoreUserRequest  $request  Datos del usuario.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return ApiResponse::success(UserResource::make($user->load('organization')), null, 201);
    }

    /**
     * Muestra el detalle de un usuario.
     *
     * Devuelve la información de un usuario específico con sus roles.
     *
     * @param  User  $user  El usuario a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(UserResource::make($user->load('organization')));
    }

    /**
     * Actualiza un usuario.
     *
     * Modifica los datos de acceso e información del usuario.
     *
     * @param  UpdateUserRequest  $request  Datos a actualizar.
     * @param  User  $user  El usuario a actualizar.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return ApiResponse::success(UserResource::make($user->load('organization')));
    }

    /**
     * Elimina un usuario.
     *
     * Elimina definitivamente el usuario del sistema.
     *
     * @param  User  $user  El usuario a eliminar.
     *
     * @throws AuthorizationException
     */
    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
