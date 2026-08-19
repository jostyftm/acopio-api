<?php

namespace App\Services\Balance;

use App\Enums\CasualtyType;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Resumen del "Balance General de Afectaciones Preliminar".
     *
     * Todas las métricas se calculan con consultas agregadas sobre la base
     * de datos para evitar consultas N+1 y mantener la respuesta eficiente.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $viviendaId = DB::table('property_types')->where('code', 'vivienda')->value('id');

        $isVivienda = fn ($query) => $query
            ->selectRaw('1')
            ->from('affectation_property_type')
            ->whereColumn('affectation_property_type.affectation_id', 'affectations.id')
            ->where('affectation_property_type.property_type_id', $viviendaId);

        $byAffectationSeverity = DB::table('affectation_severity')
            ->join('affectation_severities', 'affectation_severities.id', '=', 'affectation_severity.affectation_severity_id')
            ->join('affectations', 'affectations.id', '=', 'affectation_severity.affectation_id')
            ->whereExists($isVivienda)
            ->select('affectation_severities.code', DB::raw('count(*) as total'))
            ->groupBy('affectation_severities.code')
            ->pluck('total', 'code');

        $deceasedByCause = DB::table('casualties')
            ->join('casualty_causes', 'casualty_causes.id', '=', 'casualties.cause_id')
            ->where('casualties.type', CasualtyType::Deceased->value)
            ->select('casualty_causes.code', 'casualty_causes.display_name', DB::raw('count(*) as total'))
            ->groupBy('casualty_causes.id', 'casualty_causes.code', 'casualty_causes.display_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'code' => $row->code,
                'display_name' => $row->display_name,
                'total' => (int) $row->total,
            ])
            ->values();

        $casualtiesByType = DB::table('casualties')
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'familias_afectadas' => (int) DB::query()
                ->fromSub(
                    DB::table('family_members')->select('affectation_id', 'family_group')->distinct(),
                    'familias',
                )
                ->count(),
            'viviendas_afectadas' => (int) DB::table('affectations')
                ->whereExists($isVivienda)
                ->count(),
            'viviendas_destruidas' => (int) ($byAffectationSeverity['total'] ?? 0),
            'viviendas_averiadas' => (int) ($byAffectationSeverity['partial'] ?? 0),
            'fallecidos' => [
                'total' => (int) ($casualtiesByType[CasualtyType::Deceased->value] ?? 0),
                'por_causa' => $deceasedByCause,
            ],
            'lesionados' => (int) ($casualtiesByType[CasualtyType::Injured->value] ?? 0),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
