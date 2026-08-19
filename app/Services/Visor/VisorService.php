<?php

namespace App\Services\Visor;

use App\Models\SearchReport;
use App\Services\Balance\BalanceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VisorService
{
    public function __construct(
        private readonly BalanceService $balanceService,
    ) {}

    /**
     * Instantánea situacional para el "Visor Geográfico y Situacional".
     *
     * Combina el balance general con agregados por municipio y una muestra de
     * los puntos recientes con coordenadas. No expone datos personales: los
     * puntos incluyen solo información geográfica y conteos.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $balance = $this->balanceService->summary();

        $totalByMunicipality = DB::table('affectations')
            ->join('people', 'people.id', '=', 'affectations.person_id')
            ->select('people.municipality_id as municipality_id', DB::raw('count(*) as total'))
            ->groupBy('people.municipality_id')
            ->pluck('total', 'municipality_id');

        $severityByMunicipality = DB::table('affectation_severity')
            ->join('affectation_severities', 'affectation_severities.id', '=', 'affectation_severity.affectation_severity_id')
            ->join('affectations', 'affectations.id', '=', 'affectation_severity.affectation_id')
            ->join('people', 'people.id', '=', 'affectations.person_id')
            ->select(
                'people.municipality_id as municipality_id',
                'affectation_severities.code',
                DB::raw('count(*) as total'),
            )
            ->groupBy('people.municipality_id', 'affectation_severities.code')
            ->get()
            ->groupBy('municipality_id');

        $casualtiesByMunicipality = DB::table('casualties')
            ->join('affectations', 'affectations.id', '=', 'casualties.affectation_id')
            ->join('people', 'people.id', '=', 'affectations.person_id')
            ->select(
                'people.municipality_id as municipality_id',
                'casualties.type',
                DB::raw('count(*) as total'),
            )
            ->groupBy('people.municipality_id', 'casualties.type')
            ->get()
            ->groupBy('municipality_id');

        $departmentNames = DB::table('departments')->pluck('name', 'code');

        $municipalities = DB::table('municipalities')
            ->select('id', 'code', 'name', DB::raw('ST_Y(centroid) AS lat'), DB::raw('ST_X(centroid) AS lng'))
            ->orderBy('name')
            ->get()
            ->map(function (object $municipality) use (
                $totalByMunicipality,
                $severityByMunicipality,
                $casualtiesByMunicipality,
                $departmentNames,
            ): array {
                $id = (int) $municipality->id;
                $severities = collect($severityByMunicipality[$id] ?? []);
                $casualties = collect($casualtiesByMunicipality[$id] ?? []);

                return [
                    'id' => $id,
                    'name' => Str::title(mb_strtolower($municipality->name)),
                    'code' => $municipality->code,
                    'department' => Str::title(mb_strtolower((string) ($departmentNames[substr((string) $municipality->code, 0, 2)] ?? ''))),
                    'centroid' => [
                        'latitude' => (float) $municipality->lat,
                        'longitude' => (float) $municipality->lng,
                    ],
                    'counts' => [
                        'total' => (int) ($totalByMunicipality[$id] ?? 0),
                        'destruidas' => (int) ($severities->firstWhere('code', 'total')->total ?? 0),
                        'averiadas' => (int) ($severities->firstWhere('code', 'partial')->total ?? 0),
                        'fallecidos' => (int) ($casualties->firstWhere('type', 'deceased')->total ?? 0),
                        'lesionados' => (int) ($casualties->firstWhere('type', 'injured')->total ?? 0),
                    ],
                ];
            })
            ->values()
            ->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'kpis' => [
                'puntos_afectados' => (int) DB::table('affectations')->count(),
                'familias_afectadas' => $balance['familias_afectadas'],
                'viviendas_afectadas' => $balance['viviendas_afectadas'],
                'viviendas_destruidas' => $balance['viviendas_destruidas'],
                'viviendas_averiadas' => $balance['viviendas_averiadas'],
                'fallecidos' => $balance['fallecidos']['total'],
                'lesionados' => $balance['lesionados'],
                'desaparecidos' => (int) SearchReport::query()->pending()->count(),
            ],
            'municipalities' => $municipalities,
            'recent_points' => $this->recentPoints(),
        ];
    }

    /**
     * Muestra de los puntos recientes con coordenadas, sin datos personales.
     *
     * @return list<array<string, mixed>>
     */
    private function recentPoints(): array
    {
        $rows = DB::table('affectations')
            ->leftJoin('people', 'people.id', '=', 'affectations.person_id')
            ->leftJoin('municipalities', 'municipalities.id', '=', 'people.municipality_id')
            ->leftJoin('incident_types', 'incident_types.id', '=', 'affectations.incident_type_id')
            ->leftJoin('affectation_statuses', 'affectation_statuses.id', '=', 'affectations.status_id')
            ->whereNotNull('affectations.location')
            ->orderByDesc('affectations.created_at')
            ->limit(300)
            ->select(
                'affectations.id',
                'affectations.description',
                'affectations.address',
                'affectations.verified_at',
                'affectations.created_at',
                DB::raw('ST_Y(affectations.location) AS lat'),
                DB::raw('ST_X(affectations.location) AS lng'),
                'people.neighborhood',
                'municipalities.name as municipality_name',
                'incident_types.code as incident_code',
                'affectation_statuses.code as status_code',
            )
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $ids = $rows->pluck('id');

        $severityByAffectation = DB::table('affectation_severity')
            ->join('affectation_severities', 'affectation_severities.id', '=', 'affectation_severity.affectation_severity_id')
            ->whereIn('affectation_severity.affectation_id', $ids)
            ->select('affectation_severity.affectation_id as affectation_id', 'affectation_severities.code')
            ->get()
            ->groupBy('affectation_id');

        $propertyByAffectation = DB::table('affectation_property_type')
            ->join('property_types', 'property_types.id', '=', 'affectation_property_type.property_type_id')
            ->whereIn('affectation_property_type.affectation_id', $ids)
            ->select('affectation_property_type.affectation_id as affectation_id', 'property_types.code')
            ->get()
            ->groupBy('affectation_id');

        $casualtiesByAffectation = DB::table('casualties')
            ->whereIn('affectation_id', $ids)
            ->select('affectation_id', 'type', DB::raw('count(*) as total'))
            ->groupBy('affectation_id', 'type')
            ->get()
            ->groupBy('affectation_id');

        return $rows
            ->map(function (object $row) use ($severityByAffectation, $propertyByAffectation, $casualtiesByAffectation): array {
                $id = (int) $row->id;
                $severities = collect($severityByAffectation[$id] ?? []);
                $casualties = collect($casualtiesByAffectation[$id] ?? []);

                return [
                    'id' => $id,
                    'latitude' => (float) $row->lat,
                    'longitude' => (float) $row->lng,
                    'severity' => $severities->first()?->code,
                    'status' => $row->status_code,
                    'verified' => $row->verified_at !== null,
                    'property_types' => collect($propertyByAffectation[$id] ?? [])
                        ->pluck('code')
                        ->values()
                        ->all(),
                    'incident_type' => $row->incident_code,
                    'municipality' => $row->municipality_name !== null
                        ? Str::title(mb_strtolower($row->municipality_name))
                        : null,
                    'neighborhood' => $row->neighborhood,
                    'description' => $row->description,
                    'address' => $row->address,
                    'fallecidos' => (int) ($casualties->firstWhere('type', 'deceased')->total ?? 0),
                    'heridos' => (int) ($casualties->firstWhere('type', 'injured')->total ?? 0),
                    'created_at' => Carbon::parse($row->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
