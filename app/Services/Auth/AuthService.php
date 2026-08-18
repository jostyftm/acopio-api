<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Authenticate a user with email and password, returning an access token.
     *
     * Validates credentials against the database, checks the user is active,
     * and issues a Sanctum personal access token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{token: string, user: User}
     *
     * @throws ApiException When credentials are invalid or the user is inactive.
     */
    public function login(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw new ApiException(__('messages.invalid_credentials'), 401);
        }

        if (! $user->is_active) {
            throw new ApiException(__('messages.inactive_user'), 403);
        }

        $token = $user->createToken('spa')->accessToken;

        return [
            'token' => $token,
            'user' => $user->load('roles', 'organization'),
        ];
    }
}
