<?php

namespace Database\Seeders;

use App\Models\OrganizationType;
use Illuminate\Database\Seeder;

class OrganizationTypeSeeder extends Seeder
{
    /**
     * @var list<array{code: string, display_name: string, description: string|null}>
     */
    private const TYPES = [
        ['code' => 'fundacion', 'display_name' => 'Fundación', 'description' => 'Organización sin ánimo de lucro'],
        ['code' => 'gubernamental', 'display_name' => 'Entidad gubernamental', 'description' => 'Entidad de gobierno nacional o territorial'],
        ['code' => 'ong', 'display_name' => 'ONG', 'description' => 'Organización no gubernamental'],
        ['code' => 'privada', 'display_name' => 'Empresa privada', 'description' => 'Empresa del sector privado'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::TYPES as $type) {
            OrganizationType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }
    }
}
