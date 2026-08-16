<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\ChangePasswordRequest;
use App\Http\Requests\Api\V1\User\UpdateProfileRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\User\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MeController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Devuelve el usuario autenticado.
     *
     * Retorna los datos del usuario con sesión activa, incluyendo sus roles.
     *
     * @param  Request  $request  La petición autenticada actual.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles')->load('organization');

        return ApiResponse::success(UserResource::make($user));
    }

    /**
     * Actualiza el perfil del usuario autenticado.
     *
     * Permite cambiar nombre y correo. El correo solo es editable para
     * cuentas con contraseña; las cuentas institucionales lo conservan.
     *
     * @param  UpdateProfileRequest  $request  Datos actualizados del perfil.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile($request->user(), $request->validated());
        $user->load('roles')->load('organization');

        return ApiResponse::success(UserResource::make($user));
    }

    /**
     * Cambia la contraseña del usuario autenticado.
     *
     * Verifica la contraseña actual y actualiza la nueva. Cierra las demás
     * sesiones activas del usuario, conservando la actual.
     *
     * @param  ChangePasswordRequest  $request  Contraseñas del usuario.
     */
    public function password(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->provider !== null) {
            throw new ApiException(__('messages.password_change_not_available'), 403);
        }

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw new ApiException(__('messages.wrong_current_password'), 422);
        }

        $user->update(['password' => $request->input('password')]);

        $user->tokens()
            ->where('id', '!=', optional($user->token())->id)
            ->delete();

        return ApiResponse::success();
    }
}
