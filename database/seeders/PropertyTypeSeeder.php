<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    /**
     * @var list<array{code: string, display_name: string, description: string|null}>
     */
    private const TYPES = [
        ['code' => 'vivienda', 'display_name' => 'Vivienda', 'description' => 'Hogar o residencia particular'],
        ['code' => 'edificio', 'display_name' => 'Edificio', 'description' => 'Edificación multifamiliar o de oficinas'],
        ['code' => 'negocio', 'display_name' => 'Negocio', 'description' => 'Local comercial o establecimiento de comercio'],
        ['code' => 'hospital', 'display_name' => 'Hospital', 'description' => 'Centro de salud u hospital'],
        ['code' => 'colegio', 'display_name' => 'Colegio', 'description' => 'Institución educativa'],
        ['code' => 'hotel', 'display_name' => 'Hotel', 'description' => 'Establecimiento de hospedaje'],
        ['code' => 'bar', 'display_name' => 'Bar', 'description' => 'Establecimiento de entretenimiento o gastronomía nocturna'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::TYPES as $type) {
            PropertyType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }
    }
}
