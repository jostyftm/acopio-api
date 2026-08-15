<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialAuthController extends Controller
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_PROVIDERS = ['google', 'microsoft'];

    public function index(): View
    {
        return view('login');
    }

    public function redirect(string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        $socialUser = Socialite::driver($provider)->user();

        $user = $this->findOrCreateUser($provider, $socialUser);

        Auth::login($user);

        return redirect()->intended(config('app.url'));
    }

    private function assertSupported(string $provider): void
    {
        abort_if(! in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);
    }

    private function findOrCreateUser(string $provider, \Laravel\Socialite\Contracts\User $socialUser): User
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
}
