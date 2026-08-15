<?php

namespace App\Providers;

use App\Services\Sms\SmsProvider;
use App\Services\Sms\StubSmsProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProvider::class, function (): SmsProvider {
            $driver = (string) config('services.sms.driver', 'stub');

            return match ($driver) {
                default => new StubSmsProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('sms', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
