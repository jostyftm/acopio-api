<?php

namespace Database\Seeders;

use App\Models\FacilityType;
use Illuminate\Database\Seeder;

class FacilityTypeSeeder extends Seeder
{
    /**
     * @var list<array{code: string, display_name: string, description: string|null}>
     */
    private const TYPES = [
        ['code' => 'acopio', 'display_name' => 'Centro de acopio', 'description' => 'Punto de recepción y distribución de donaciones'],
        ['code' => 'albergue', 'display_name' => 'Albergue', 'description' => 'Espacio para alojamiento temporal de personas afectadas'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::TYPES as $type) {
            FacilityType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }
    }
}
