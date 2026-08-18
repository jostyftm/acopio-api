<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

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
        $result = $this->service->login($request->validated());

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => UserResource::make($result['user']),
        ]);
    }
}
