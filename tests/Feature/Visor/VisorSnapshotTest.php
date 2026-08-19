<?php

use App\Models\Department;
use App\Models\Municipality;
use Clickbar\Magellan\Data\Geometries\Point;

function makeVisorMunicipality(): Municipality
{
    $department = Department::query()->create([
        'code' => '76',
        'name' => 'Valle del Cauca',
        'normalized_name' => 'VALLE DEL CAUCA',
    ]);

    return Municipality::query()->create([
        'department_id' => $department->id,
        'code' => '76001',
        'name' => 'Cali',
        'normalized_name' => 'CALI',
        'centroid' => Point::makeGeodetic(3.45, -76.53),
        'boundary' => 'MULTIPOLYGON(((-76.6 3.4, -76.5 3.4, -76.5 3.5, -76.6 3.4)))',
    ]);
}

it('exposes the situational snapshot via a public endpoint', function () {
    $municipality = makeVisorMunicipality();

    $affectation = makeAffectation();
    $affectation->person()->update(['municipality_id' => $municipality->id]);
    $affectation->update([
        'location' => Point::makeGeodetic(3.45, -76.53),
        'verified_at' => now(),
    ]);

    $this->getJson('/api/v1/visor')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'generated_at',
                'kpis' => [
                    'puntos_afectados',
                    'familias_afectadas',
                    'viviendas_afectadas',
                    'viviendas_destruidas',
                    'viviendas_averiadas',
                    'fallecidos',
                    'lesionados',
                    'desaparecidos',
                ],
                'municipalities' => [[
                    'id',
                    'name',
                    'code',
                    'department',
                    'centroid' => ['latitude', 'longitude'],
                    'counts' => ['total', 'destruidas', 'averiadas', 'fallecidos', 'lesionados'],
                ]],
                'recent_points' => [[
                    'id',
                    'latitude',
                    'longitude',
                    'severity',
                    'status',
                    'verified',
                    'property_types',
                    'incident_type',
                    'municipality',
                    'neighborhood',
                    'description',
                    'address',
                    'fallecidos',
                    'heridos',
                    'created_at',
                ]],
            ],
        ])
        ->assertJsonPath('data.kpis.puntos_afectados', 1)
        ->assertJsonPath('data.municipalities.0.name', 'Cali')
        ->assertJsonPath('data.municipalities.0.counts.total', 1)
        ->assertJsonPath('data.municipalities.0.counts.destruidas', 0)
        ->assertJsonPath('data.municipalities.0.counts.averiadas', 1)
        ->assertJsonPath('data.recent_points.0.municipality', 'Cali')
        ->assertJsonPath('data.recent_points.0.severity', 'partial')
        ->assertJsonPath('data.recent_points.0.verified', true)
        ->assertJsonPath('data.recent_points.0.fallecidos', 0);
});

it('does not expose personal data in the visor points', function () {
    $affectation = makeAffectation();
    $affectation->update(['location' => Point::makeGeodetic(3.45, -76.53)]);

    $this->getJson('/api/v1/visor')
        ->assertOk()
        ->assertJsonMissingPath('data.recent_points.0.full_name')
        ->assertJsonMissingPath('data.recent_points.0.document_number')
        ->assertJsonMissingPath('data.recent_points.0.phone');
});
