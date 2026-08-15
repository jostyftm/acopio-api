<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService,
    ) {}

    public function callback(string $provider): RedirectResponse
    {
        abort_unless($this->socialAuthService->isSupported($provider), 404);

        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = $this->socialAuthService->findOrCreateFromProvider($provider, $socialUser);

        $token = $this->socialAuthService->issueAccessToken($user);

        return redirect()->away(
            config('app.frontend_url').'/auth/callback#access_token='.$token.'&token_type=Bearer',
        );
    }
}
