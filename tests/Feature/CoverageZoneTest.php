<?php

use App\Models\CoverageZone;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Organization;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $department = Department::query()->create([
        'code' => '76',
        'name' => 'Valle del Cauca',
        'normalized_name' => 'VALLE DEL CAUCA',
    ]);

    $this->municipality = Municipality::query()->create([
        'department_id' => $department->id,
        'code' => '76001',
        'name' => 'Cali',
        'normalized_name' => 'CALI',
        'boundary' => 'MULTIPOLYGON(((-76.6 3.4, -76.5 3.4, -76.5 3.5, -76.6 3.4)))',
    ]);
});

function makeCoverageZonePayload(int $municipalityId, array $attributes = []): array
{
    return array_merge([
        'municipality_id' => $municipalityId,
        'name' => 'Zona Centro',
        'polygon' => 'POLYGON((-76.6 3.4, -76.5 3.4, -76.5 3.5, -76.6 3.4))',
    ], $attributes);
}

it('lets an admin create, update and delete a coverage zone', function () {
    $admin = makeUserWithRole('admin');
    Passport::actingAs($admin);

    $this->postJson('/api/v1/coverage-zones', makeCoverageZonePayload($this->municipality->id))
        ->assertCreated()
        ->assertJsonPath('data.attributes.name', 'Zona Centro')
        ->assertJsonPath('data.attributes.polygon.type', 'Polygon');

    $zone = CoverageZone::query()->firstOrFail();

    $this->putJson("/api/v1/coverage-zones/{$zone->id}", ['name' => 'Zona Norte'])
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'Zona Norte');

    $this->deleteJson("/api/v1/coverage-zones/{$zone->id}")->assertNoContent();

    expect(CoverageZone::query()->count())->toBe(0);
});

it('rejects coverage zone management for non-admins', function () {
    $operator = makeUserWithRole('operator');
    Passport::actingAs($operator);

    $this->postJson('/api/v1/coverage-zones', makeCoverageZonePayload($this->municipality->id))->assertStatus(403);
});

it('lets an admin assign coverage zones to an organization', function () {
    $admin = makeUserWithRole('admin');
    Passport::actingAs($admin);

    $organization = Organization::factory()->create();

    $zoneA = CoverageZone::query()->create(makeCoverageZonePayload($this->municipality->id, ['name' => 'Zona A']));
    $zoneB = CoverageZone::query()->create(makeCoverageZonePayload($this->municipality->id, ['name' => 'Zona B']));

    $this->putJson("/api/v1/organizations/{$organization->id}/coverage", [
        'coverage_zone_ids' => [$zoneA->id, $zoneB->id],
    ])->assertOk()
        ->assertJsonCount(2, 'data.attributes.coverage_zones');

    expect($organization->fresh()->coverageZones->pluck('id')->all())->toBe([$zoneA->id, $zoneB->id]);

    $this->putJson("/api/v1/organizations/{$organization->id}/coverage", [
        'coverage_zone_ids' => [$zoneB->id],
    ])->assertOk()
        ->assertJsonCount(1, 'data.attributes.coverage_zones');

    expect($organization->fresh()->coverageZones->pluck('id')->all())->toBe([$zoneB->id]);
});

it('resolves the coverage zone that covers a reported location', function () {
    $zone = CoverageZone::query()->create(makeCoverageZonePayload($this->municipality->id));

    $inside = CoverageZone::findForCoordinates(3.43, -76.55);

    expect($inside?->id)->toBe($zone->id);

    expect(CoverageZone::findForCoordinates(0.5, -90.0))->toBeNull();
});

it('resolves the organizations covering a reported location', function () {
    $zone = CoverageZone::query()->create(makeCoverageZonePayload($this->municipality->id));
    $covered = Organization::factory()->create();
    $uncovered = Organization::factory()->create();

    $covered->coverageZones()->attach($zone);

    $resolved = $zone->organizations()->pluck('organizations.id')->all();

    expect($resolved)->toBe([$covered->id])
        ->and($resolved)->not->toContain($uncovered->id);
});
