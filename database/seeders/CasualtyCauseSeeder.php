<?php

namespace Database\Seeders;

use App\Models\CasualtyCause;
use Illuminate\Database\Seeder;

class CasualtyCauseSeeder extends Seeder
{
    /**
     * @var list<array{code: string, display_name: string, description: string|null}>
     */
    private const CAUSES = [
        ['code' => 'aplastamiento', 'display_name' => 'Por aplastamiento', 'description' => 'Personas fallecidas por el colapso de estructuras o escombros.'],
        ['code' => 'sismo', 'display_name' => 'Eventos relacionados con el sismo', 'description' => 'Personas fallecidas a causa directa del evento sísmico (caídas, impactos, etc.).'],
        ['code' => 'quemaduras', 'display_name' => 'Quemaduras', 'description' => 'Personas fallecidas a causa de quemaduras graves.'],
        ['code' => 'ahogamiento', 'display_name' => 'Ahogamiento', 'description' => 'Personas fallecidas por inundación o arrastre de corrientes.'],
        ['code' => 'otros', 'display_name' => 'Otras causas', 'description' => 'Causas de fallecimiento no clasificadas en las anteriores.'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CAUSES as $cause) {
            CasualtyCause::query()->updateOrCreate(
                ['code' => $cause['code']],
                [
                    'display_name' => $cause['display_name'],
                    'description' => $cause['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
