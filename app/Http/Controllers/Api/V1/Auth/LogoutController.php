<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Cierra la sesión del usuario autenticado.
     *
     * Revoca el token de acceso actual, invalidándolo en el servidor.
     *
     * @param  Request  $request  La petición autenticada actual.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->token()?->revoke();

        return ApiResponse::success();
    }
}
