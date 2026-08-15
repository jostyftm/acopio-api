<?php

namespace App\Providers;

use App\Models\PassportClient;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::useClientModel(PassportClient::class);
        Passport::enablePasswordGrant();
        Passport::authorizationView('oauth.authorize');
        Passport::tokensExpireIn(now()->addHours(12));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
