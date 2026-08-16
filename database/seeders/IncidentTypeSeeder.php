<?php

namespace Database\Seeders;

use App\Enums\SeverityMode;
use App\Models\AffectationSeverity;
use App\Models\IncidentType;
use Illuminate\Database\Seeder;

class IncidentTypeSeeder extends Seeder
{
    /**
     * @var list<array{
     *     code: string,
     *     display_name: string,
     *     description: string|null,
     *     severity_mode: SeverityMode,
     *     severities: list<array{code: string, display_name: string, description: string|null, order: int}>
     * }>
     */
    private const TYPES = [
        [
            'code' => 'derrumbes',
            'display_name' => 'Derrumbes',
            'description' => 'Deslizamientos o colapso de terrenos que afectan viviendas e infraestructura.',
            'severity_mode' => SeverityMode::Single,
            'severities' => [
                ['code' => 'partial', 'display_name' => 'Afectación parcial', 'description' => 'El predio presenta daños parciales', 'order' => 1],
                ['code' => 'total', 'display_name' => 'Afectación total', 'description' => 'El predio quedó destruido o inhabitado', 'order' => 2],
            ],
        ],
        [
            'code' => 'incendios',
            'display_name' => 'Incendios',
            'description' => 'Incendios estructurales o de cobertura vegetal que afectan predios.',
            'severity_mode' => SeverityMode::Single,
            'severities' => [
                ['code' => 'partial', 'display_name' => 'Afectación parcial', 'description' => 'El predio presenta daños parciales por fuego', 'order' => 1],
                ['code' => 'total', 'display_name' => 'Afectación total', 'description' => 'El predio quedó destruido por el fuego', 'order' => 2],
            ],
        ],
        [
            'code' => 'alteracion_orden_publico',
            'display_name' => 'Alteración del orden público',
            'description' => 'Disturbios, confrontaciones o situaciones que afectan la seguridad ciudadana.',
            'severity_mode' => SeverityMode::Multiple,
            'severities' => [
                ['code' => 'heridos', 'display_name' => 'Heridos', 'description' => 'Hay personas heridas en el incidente', 'order' => 1],
                ['code' => 'fallecidos', 'display_name' => 'Personas fallecidas', 'description' => 'Hay personas fallecidas en el incidente', 'order' => 2],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::TYPES as $type) {
            $incidentType = IncidentType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'display_name' => $type['display_name'],
                    'description' => $type['description'],
                    'severity_mode' => $type['severity_mode'],
                ],
            );

            foreach ($type['severities'] as $severity) {
                AffectationSeverity::query()->updateOrCreate(
                    ['incident_type_id' => $incidentType->id, 'code' => $severity['code']],
                    [
                        'display_name' => $severity['display_name'],
                        'description' => $severity['description'],
                        'order' => $severity['order'],
                    ],
                );
            }
        }
    }
}
