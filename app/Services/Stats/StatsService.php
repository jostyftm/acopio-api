<?php

namespace App\Services\Stats;

use App\Enums\PersonStatus;
use App\Models\Affectation;
use App\Models\Need;
use App\Models\Person;
use App\Models\SearchReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $byAffectationSeverity = DB::table('affectation_severity')
            ->join('affectation_severities', 'affectation_severities.id', '=', 'affectation_severity.affectation_severity_id')
            ->select('affectation_severities.code', DB::raw('count(*) as total'))
            ->groupBy('affectation_severities.code')
            ->pluck('total', 'code');

        $needsByImpactLevel = DB::table('affectation_need')
            ->join('needs', 'needs.id', '=', 'affectation_need.need_id')
            ->join('severity_needs', 'severity_needs.id', '=', 'needs.severity_need_id')
            ->select('severity_needs.code_level', DB::raw('count(*) as total'))
            ->groupBy('severity_needs.code_level')
            ->pluck('total', 'code_level');

        return [
            'total_people' => (int) Person::query()->count(),
            'by_status' => [
                'registered' => (int) ($byStatus[PersonStatus::Registered->value] ?? 0),
                'verified' => (int) ($byStatus[PersonStatus::Verified->value] ?? 0),
                'located' => (int) ($byStatus[PersonStatus::Located->value] ?? 0),
            ],
            'by_municipality' => Person::query()
                ->join('municipalities', 'municipalities.id', '=', 'people.municipality_id')
                ->select('municipalities.name', DB::raw('count(*) as total'))
                ->groupBy('municipalities.name')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->mapWithKeys(
                    fn ($row): array => [Str::title(mb_strtolower($row->name)) => (int) $row->total],
                ),
            'people_with_location' => (int) Person::query()->withLocation()->count(),
            'total_affected_people' => (int) Affectation::query()->count(),
            'by_affectation_severity' => [
                'partial' => (int) ($byAffectationSeverity['partial'] ?? 0),
                'total' => (int) ($byAffectationSeverity['total'] ?? 0),
            ],
            'by_property_type' => DB::table('affectation_property_type')
                ->join('property_types', 'property_types.id', '=', 'affectation_property_type.property_type_id')
                ->select('property_types.id', 'property_types.display_name', DB::raw('count(*) as total'))
                ->groupBy('property_types.id', 'property_types.display_name')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->id,
                    'display_name' => $row->display_name,
                    'total' => (int) $row->total,
                ])
                ->values(),
            'by_incident_type' => DB::table('affectations')
                ->join('incident_types', 'incident_types.id', '=', 'affectations.incident_type_id')
                ->select('incident_types.id', 'incident_types.display_name', DB::raw('count(*) as total'))
                ->groupBy('incident_types.id', 'incident_types.display_name')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->id,
                    'display_name' => $row->display_name,
                    'total' => (int) $row->total,
                ])
                ->values(),
            'needs_by_impact_level' => [
                'high' => (int) ($needsByImpactLevel['high'] ?? 0),
                'medium' => (int) ($needsByImpactLevel['medium'] ?? 0),
                'low' => (int) ($needsByImpactLevel['low'] ?? 0),
            ],
            'top_needs' => Need::query()
                ->withCount('affectations')
                ->orderByDesc('affectations_count')
                ->limit(5)
                ->get()
                ->map(fn (Need $need): array => [
                    'name' => $need->name,
                    'total' => $need->affectations_count,
                ])
                ->values(),
            'pending_search_reports' => (int) SearchReport::query()->pending()->count(),
            'total_search_reports' => (int) SearchReport::query()->count(),
        ];
    }
}
