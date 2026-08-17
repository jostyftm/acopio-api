<?php

namespace Database\Seeders;

use App\Enums\ImpactLevel;
use App\Models\IncidentType;
use App\Models\Need;
use App\Models\SeverityNeed;
use Illuminate\Database\Seeder;

class NeedsSeeder extends Seeder
{
    /**
     * @var list<array{code_level: string, display_name: string}>
     */
    private const SEVERITY_LEVELS = [
        ['code_level' => 'high', 'display_name' => 'Alto'],
        ['code_level' => 'medium', 'display_name' => 'Medio'],
        ['code_level' => 'low', 'display_name' => 'Bajo'],
    ];

    /**
     * @var list<array{name: string, description: string|null, code_level: string}>
     */
    private const NEEDS = [
        ['name' => 'Dónde dormir', 'description' => null, 'code_level' => 'high'],
        ['name' => 'Comida', 'description' => null, 'code_level' => 'medium'],
        ['name' => 'Pañales', 'description' => 'Pañales para bebés y adultos mayores', 'code_level' => 'medium'],
        ['name' => 'Ropa', 'description' => null, 'code_level' => 'medium'],
        ['name' => 'Kit de aseo', 'description' => null, 'code_level' => 'medium'],
        ['name' => 'Atención médica', 'description' => null, 'code_level' => 'medium'],
        ['name' => 'Medicamentos', 'description' => null, 'code_level' => 'medium'],
        ['name' => 'Albergue', 'description' => null, 'code_level' => 'high'],
        ['name' => 'Transporte', 'description' => null, 'code_level' => 'medium'],
        ['name' => 'Reconstrucción', 'description' => 'Materiales o apoyo para reconstruir la vivienda', 'code_level' => 'high'],
        ['name' => 'Niños', 'description' => null, 'code_level' => 'low'],
        ['name' => 'Adultos mayores', 'description' => null, 'code_level' => 'low'],
        ['name' => 'Discapacidad', 'description' => null, 'code_level' => 'low'],
        ['name' => 'Embarazadas', 'description' => null, 'code_level' => 'low'],
        ['name' => 'Enfermedad crónica', 'description' => null, 'code_level' => 'medium'],
    ];

    /**
     * Necesidades asociadas a cada tipo de incidente (por código del tipo).
     *
     * @var array<string, list<string>>
     */
    private const INCIDENT_TYPE_NEEDS = [
        'derrumbes' => ['Dónde dormir', 'Comida', 'Ropa', 'Kit de aseo', 'Atención médica', 'Medicamentos', 'Albergue', 'Transporte', 'Reconstrucción', 'Niños', 'Adultos mayores', 'Discapacidad', 'Embarazadas', 'Enfermedad crónica', 'Pañales'],
        'incendios' => ['Dónde dormir', 'Comida', 'Ropa', 'Kit de aseo', 'Atención médica', 'Medicamentos', 'Albergue', 'Transporte', 'Reconstrucción', 'Niños', 'Adultos mayores', 'Discapacidad', 'Embarazadas', 'Enfermedad crónica', 'Pañales'],
        'alteracion_orden_publico' => ['Atención médica', 'Medicamentos', 'Transporte', 'Niños', 'Adultos mayores', 'Discapacidad', 'Embarazadas', 'Enfermedad crónica'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = collect(self::SEVERITY_LEVELS)->mapWithKeys(fn (array $level): array => [
            $level['code_level'] => SeverityNeed::query()->updateOrCreate(
                ['code_level' => $level['code_level']],
                ['display_name' => $level['display_name']],
            ),
        ]);

        $needsByNormalizedName = [];

        foreach (self::NEEDS as $need) {
            $model = Need::query()->updateOrCreate(
                ['normalized_name' => Need::normalizeName($need['name'])],
                [
                    'name' => $need['name'],
                    'description' => $need['description'],
                    'severity_need_id' => $levels[ImpactLevel::from($need['code_level'])->value]->id,
                ],
            );

            $needsByNormalizedName[$model->normalized_name] = $model;
        }

        $this->linkIncidentTypeNeeds($needsByNormalizedName);
    }

    /**
     * @param  array<string, Need>  $needsByNormalizedName
     */
    private function linkIncidentTypeNeeds(array $needsByNormalizedName): void
    {
        foreach (self::INCIDENT_TYPE_NEEDS as $code => $needNames) {
            $incidentType = IncidentType::query()->where('code', $code)->first();

            if ($incidentType === null) {
                continue;
            }

            $needs = collect($needNames)
                ->map(fn (string $name): ?Need => $needsByNormalizedName[Need::normalizeName($name)] ?? null)
                ->filter()
                ->pluck('id');

            $incidentType->needs()->sync($needs);
        }
    }
}
