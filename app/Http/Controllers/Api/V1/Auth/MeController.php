<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
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
        $user->load('roles');

        return ApiResponse::success(UserResource::make($user));
    }
}
