<?php

namespace App\Services\Stats;

use App\Enums\PersonStatus;
use App\Models\Person;
use App\Models\SearchReport;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $byStatus = Person::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total_people' => (int) Person::query()->count(),
            'by_status' => [
                'registered' => (int) ($byStatus[PersonStatus::Registered->value] ?? 0),
                'verified' => (int) ($byStatus[PersonStatus::Verified->value] ?? 0),
                'located' => (int) ($byStatus[PersonStatus::Located->value] ?? 0),
            ],
            'by_municipality' => Person::query()
                ->select('municipality', DB::raw('count(*) as total'))
                ->groupBy('municipality')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->pluck('total', 'municipality'),
            'people_with_location' => (int) Person::query()->withLocation()->count(),
            'pending_search_reports' => (int) SearchReport::query()->pending()->count(),
            'total_search_reports' => (int) SearchReport::query()->count(),
        ];
    }
}
