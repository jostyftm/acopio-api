<?php

use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\SocialLoginController;
use App\Http\Controllers\Api\V1\Person\PersonController;
use App\Http\Controllers\Api\V1\Registration\RegistrationController;
use App\Http\Controllers\Api\V1\SearchReport\SearchReportController;
use App\Http\Controllers\Api\V1\Sms\SmsWebhookController;
use App\Http\Controllers\Api\V1\Stats\StatsController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

Route::name('api.v1.')->group(function (): void {
    // Public endpoints.
    Route::middleware('throttle:public')
        ->post('registrations', [RegistrationController::class, 'store'])
        ->name('registrations.store');

    Route::get('people/search', [PersonController::class, 'search'])->name('people.search');

    Route::get('auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])
        ->name('auth.social.redirect');

    Route::post('search-reports', [SearchReportController::class, 'store'])
        ->name('search-reports.store');

    Route::middleware('throttle:sms')
        ->post('sms/webhook', [SmsWebhookController::class, 'store'])
        ->name('sms.webhook');

    // Authenticated endpoints.
    Route::middleware(['auth:api', 'throttle:auth'])->group(function (): void {
        Route::get('me', MeController::class)->name('me');

        Route::get('stats', [StatsController::class, 'show'])->name('stats.show');

        Route::apiResource('people', PersonController::class)->except(['store']);
        Route::post('people/{person}/verify', [PersonController::class, 'verify'])->name('people.verify');

        Route::apiResource('search-reports', SearchReportController::class)->except(['store', 'destroy']);
        Route::apiResource('users', UserController::class);
    });
});
