<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\AffectationStatus;
use Illuminate\Database\Seeder;

class AffectationStatusSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Reportada',
                'code' => 'reported',
                'icon' => 'CircleAlert',
                'text_color' => 'text-amber-700 dark:text-amber-400',
                'bg_color' => 'bg-amber-100 dark:bg-amber-900/30',
            ],
            [
                'name' => 'Verificada',
                'code' => 'verified',
                'icon' => 'ShieldCheck',
                'text_color' => 'text-blue-700 dark:text-blue-400',
                'bg_color' => 'bg-blue-100 dark:bg-blue-900/30',
            ],
            [
                'name' => 'Localizada',
                'code' => 'located',
                'icon' => 'MapPin',
                'text_color' => 'text-green-700 dark:text-green-400',
                'bg_color' => 'bg-green-100 dark:bg-green-900/30',
            ],
        ];

        $byCode = [];

        foreach ($statuses as $status) {
            $byCode[$status['code']] = AffectationStatus::query()->updateOrCreate(
                ['code' => $status['code']],
                $status,
            );
        }

        if (Affectation::query()->whereNull('status_id')->exists()) {
            Affectation::query()
                ->whereNull('status_id')
                ->update(['status_id' => $byCode['reported']->id]);
        }
    }
}
