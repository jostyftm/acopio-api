<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Module\ModuleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeModulesController extends Controller
{
    public function __construct(
        private readonly ModuleService $moduleService,
    ) {}

    /**
     * Devuelve los módulos accesibles del usuario autenticado.
     *
     * Cada módulo incluye el mapa de permisos `{accion: bool}` que el frontend
     * utiliza para mostrar u ocultar acciones según el rol del usuario.
     *
     * @param  Request  $request  La petición autenticada actual.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $modules = $this->moduleService->treeFor($request->user());

        return ApiResponse::success($modules);
    }
}
