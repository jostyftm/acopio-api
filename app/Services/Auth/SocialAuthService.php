<?php

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialAuthService
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_PROVIDERS = ['google', 'microsoft'];

    public function redirectUrl(string $provider): string
    {
        return Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
    }

    public function findOrCreateFromProvider(string $provider, SocialUser $socialUser): User
    {
        $user = User::query()->firstOrCreate(
            ['provider' => $provider, 'provider_id' => $socialUser->getId()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getEmail(),
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ],
        );

        if (! $user->roles()->exists()) {
            $user->assignRole(Role::findByName('viewer', 'api'));
        }

        return $user;
    }

    public function issueAccessToken(User $user): string
    {
        return $user->createToken('spa')->accessToken;
    }

    public function isSupported(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS, true);
    }
}
