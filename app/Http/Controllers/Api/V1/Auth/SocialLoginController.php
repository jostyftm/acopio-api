<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SocialLoginController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService,
    ) {}

    /**
     * Devuelve la URL de inicio de sesión con un proveedor social.
     *
     * Genera la URL de autorización de Google o Microsoft para el flujo SSO
     * del SPA. El navegador debe redirigirse a la URL devuelta; el callback
     * del proveedor redirige al frontend con el token de acceso en el
     * fragmento de la URL (`#access_token=...`).
     *
     * @param  string  $provider  Proveedor social: `google` o `microsoft`.
     */
    public function redirect(string $provider): JsonResponse
    {
        abort_unless($this->socialAuthService->isSupported($provider), 404);

        return ApiResponse::success([
            'url' => $this->socialAuthService->redirectUrl($provider),
        ]);
    }
}
