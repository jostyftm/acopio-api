<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Inicia sesión con correo y contraseña.
     *
     * Valida las credenciales de un usuario del sistema y emite un token de
     * acceso. Rechaza cuentas desactivadas.
     *
     * @param  LoginRequest  $request  Credenciales del usuario.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->input('email'))->first();

        if ($user === null || ! Hash::check($request->input('password'), $user->password)) {
            throw new ApiException(__('messages.invalid_credentials'), 401);
        }

        if (! $user->is_active) {
            throw new ApiException(__('messages.inactive_user'), 403);
        }

        $token = $user->createToken('spa')->accessToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => UserResource::make($user->load('roles')->load('organization')),
        ]);
    }
}
