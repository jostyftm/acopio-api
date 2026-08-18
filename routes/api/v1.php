<?php

use App\Http\Controllers\Api\V1\Affectation\AffectationController;
use App\Http\Controllers\Api\V1\AffectationSeverity\AffectationSeverityController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\MeModulesController;
use App\Http\Controllers\Api\V1\Auth\SocialLoginController;
use App\Http\Controllers\Api\V1\CoverageZone\CoverageZoneController;
use App\Http\Controllers\Api\V1\Department\DepartmentController;
use App\Http\Controllers\Api\V1\Facility\FacilityController;
use App\Http\Controllers\Api\V1\FacilityType\FacilityTypeController;
use App\Http\Controllers\Api\V1\IncidentType\IncidentTypeController;
use App\Http\Controllers\Api\V1\Module\ModuleController;
use App\Http\Controllers\Api\V1\Municipality\MunicipalityController;
use App\Http\Controllers\Api\V1\Need\NeedController;
use App\Http\Controllers\Api\V1\Organization\OrganizationController;
use App\Http\Controllers\Api\V1\OrganizationType\OrganizationTypeController;
use App\Http\Controllers\Api\V1\Permission\PermissionController;
use App\Http\Controllers\Api\V1\Person\PersonController;
use App\Http\Controllers\Api\V1\PropertyType\PropertyTypeController;
use App\Http\Controllers\Api\V1\Registration\RegistrationController;
use App\Http\Controllers\Api\V1\Role\RoleController;
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

    Route::middleware('throttle:public')
        ->post('auth/login', LoginController::class)
        ->name('auth.login');

    Route::get('registrations/check', [RegistrationController::class, 'check'])
        ->name('registrations.check');

    Route::get('people/search', [PersonController::class, 'search'])->name('people.search');

    Route::get('municipalities', [MunicipalityController::class, 'index'])->name('municipalities.index');

    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');

    Route::get('needs', [NeedController::class, 'index'])->name('needs.index');

    Route::get('organization-types', [OrganizationTypeController::class, 'index'])
        ->name('organization-types.index');

    Route::get('facility-types', [FacilityTypeController::class, 'index'])
        ->name('facility-types.index');

    Route::get('property-types', [PropertyTypeController::class, 'index'])
        ->name('property-types.index');

    Route::get('incident-types', [IncidentTypeController::class, 'index'])
        ->name('incident-types.index');

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
        Route::put('me', [MeController::class, 'update'])->name('me.update');
        Route::post('me/password', [MeController::class, 'password'])->name('me.password');
        Route::post('auth/logout', LogoutController::class)->name('auth.logout');

        Route::get('me/modules', MeModulesController::class)->name('me.modules');

        Route::get('stats', [StatsController::class, 'show'])->name('stats.show');

        Route::apiResource('modules', ModuleController::class);
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);

        Route::apiResource('people', PersonController::class)->except(['store']);
        Route::post('people/{person}/verify', [PersonController::class, 'verify'])->name('people.verify');

        Route::post('needs', [NeedController::class, 'store'])->name('needs.store');

        Route::get('affectations', [AffectationController::class, 'index'])
            ->name('affectations.index');
        Route::post('affectations', [AffectationController::class, 'store'])
            ->name('affectations.store');
        Route::post('affectations/report', [AffectationController::class, 'storeReport'])
            ->name('affectations.report');
        Route::get('affectations/{affectation}', [AffectationController::class, 'show'])
            ->name('affectations.show');
        Route::put('affectations/{affectation}', [AffectationController::class, 'update'])
            ->name('affectations.update');
        Route::delete('affectations/{affectation}', [AffectationController::class, 'destroy'])
            ->name('affectations.destroy');
        Route::delete('affectations/{affectation}/evidence/{evidence}', [AffectationController::class, 'destroyEvidence'])
            ->name('affectations.evidence.destroy');
        Route::post('affectations/{affectation}/verify', [AffectationController::class, 'verify'])
            ->name('affectations.verify');

        Route::apiResource('search-reports', SearchReportController::class)->except(['store', 'destroy']);
        Route::apiResource('users', UserController::class);

        Route::apiResource('organizations', OrganizationController::class);
        Route::put('organizations/{organization}/coverage', [OrganizationController::class, 'updateCoverage'])
            ->name('organizations.coverage');
        Route::post('organizations/{organization}/user', [OrganizationController::class, 'storeUser'])
            ->name('organizations.storeUser');

        Route::apiResource('coverage-zones', CoverageZoneController::class);

        Route::apiResource('facilities', FacilityController::class);
        Route::post('facilities/{facility}/photos', [FacilityController::class, 'storePhotos'])
            ->name('facilities.storePhotos');

        Route::apiResource('organization-types', OrganizationTypeController::class)->except(['index']);
        Route::apiResource('facility-types', FacilityTypeController::class)->except(['index']);
        Route::apiResource('property-types', PropertyTypeController::class)->except(['index']);
        Route::apiResource('incident-types', IncidentTypeController::class)->except(['index']);
        Route::apiResource('affectation-severities', AffectationSeverityController::class)->except(['index']);
    });
});
