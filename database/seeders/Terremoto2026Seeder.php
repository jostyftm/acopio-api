<?php

namespace Database\Seeders;

use App\Models\AffectationSeverity;
use App\Models\CasualtyCause;
use App\Models\IncidentType;
use App\Models\PropertyType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reproduce, a escala real, las afectaciones del sismo del 10 de agosto de 2026
 * (epicentro San José del Palmar, Chocó) con base en el balance UNGRD del
 * 18 de agosto de 2026 (6:30 a. m.).
 *
 * Idempotente: borra los datos generados en una ejecución previa y los reemplaza.
 * Usa inserciones masivas por lotes (sin Eloquent por fila) para completar el
 * volumen en tiempos razonables.
 */
class Terremoto2026Seeder extends Seeder
{
    private const DESTROYED_VILLAS = 29554;

    private const DAMAGED_VILLAS = 134342;

    private const COLLAPSED_BUILDINGS = 267;

    private const AFFECTED_SCHOOLS = 3443;

    private const AFFECTED_HOSPITALS = 303;

    private const DECEASED = 304;

    private const INJURED = 4548;

    private const MISSING_REPORTS = 426;

    private const FOUND_REPORTS = 356;

    private const SHELTERS = 800;

    private const COLLECTION_CENTERS = 400;

    private const CHUNK = 2000;

    private const MAX_PARAMS = 60000;

    private const T0 = 1786406400; // 2026-08-10 08:00:00 UTC

    private const T1 = 1787164200; // 2026-08-18 06:30:00 UTC

    /**
     * @var array<string, array{weight: float, bonus: array<string, int>}>
     */
    private const DEPARTMENTS = [
        '76' => [
            'weight' => 30,
            'bonus' => [
                'CALI' => 60, 'PALMIRA' => 14, 'BUENAVENTURA' => 10, 'TULUÁ' => 8, 'GUADALAJARA DE BUGA' => 8,
                'CARTAGO' => 6, 'DAGUA' => 5, 'YUMBO' => 5, 'JAMUNDÍ' => 4, 'CANDELARIA' => 3,
                'EL CERRITO' => 3, 'FLORIDA' => 3, 'ROLDANILLO' => 3, 'PRADERA' => 2, 'LA UNIÓN' => 2,
                'SEVILLA' => 2, 'ZARZAL' => 2,
            ],
        ],
        '66' => ['weight' => 20, 'bonus' => ['PEREIRA' => 60, 'DOSQUEBRADAS' => 20, 'SANTA ROSA DE CABAL' => 8, 'LA VIRGINIA' => 7, 'LA CELIA' => 5, 'MARSELLA' => 4]],
        '27' => ['weight' => 12, 'bonus' => ['QUIBDÓ' => 20, 'SAN JOSÉ DEL PALMAR' => 12, 'ISTMINA' => 10, 'TADÓ' => 8, 'CONDOTO' => 8, 'NÓVITA' => 6, 'SIPÍ' => 6, 'CÉRTEGUI' => 5, 'MEDIO SAN JUAN' => 4, 'LLORÓ' => 3, 'RÍO QUITO' => 3, 'EL LITORAL DEL SAN JUAN' => 3]],
        '17' => ['weight' => 10, 'bonus' => ['MANIZALES' => 40, 'CHINCHINÁ' => 12, 'VILLAMARÍA' => 10, 'PALESTINA' => 6, 'LA DORADA' => 5, 'ANSERMA' => 4, 'NEIRA' => 4, 'RÍOSUCIO' => 3]],
        '63' => ['weight' => 10, 'bonus' => ['ARMENIA' => 40, 'CALARCÁ' => 12, 'MONTENEGRO' => 8, 'LA TEBAIDA' => 7, 'CIRCASIA' => 5, 'QUIMBAYA' => 4, 'FILANDIA' => 3]],
        '19' => ['weight' => 8, 'bonus' => ['POPAYÁN' => 20, 'SANTANDER DE QUILICHAO' => 10, 'PIENDAMÓ - TUNÍA' => 6, 'SILVIA' => 4, 'CAJIBÍO' => 4, 'TIMBÍO' => 4, 'PATÍA' => 3, 'MIRANDA' => 4, 'CORINTO' => 3, 'CALDONO' => 3]],
        '05' => ['weight' => 4, 'bonus' => ['MEDELLÍN' => 15, 'URRAO' => 6, 'ANDES' => 5, 'JARDÍN' => 4, 'CIUDAD BOLÍVAR' => 4, 'ITAGÜÍ' => 3, 'BELLO' => 3]],
        '73' => ['weight' => 2, 'bonus' => ['IBAGUÉ' => 10]],
        '25' => ['weight' => 1.5, 'bonus' => ['SOACHA' => 4, 'FACATATIVÁ' => 3, 'MADRID' => 3, 'MOSQUERA' => 2]],
        '52' => ['weight' => 1, 'bonus' => ['PASTO' => 4, 'TUMACO' => 3, 'IPIALES' => 2]],
        '41' => ['weight' => 0.6, 'bonus' => ['NEIVA' => 3, 'PITALITO' => 2]],
        '68' => ['weight' => 0.5, 'bonus' => ['BUCARAMANGA' => 3, 'FLORIDABLANCA' => 2]],
        '54' => ['weight' => 0.3, 'bonus' => ['CÚCUTA' => 2, 'OCAÑA' => 2]],
        '13' => ['weight' => 0.3, 'bonus' => ['CARTAGENA' => 2]],
        '20' => ['weight' => 0.3, 'bonus' => ['VALLEDUPAR' => 2]],
    ];

    /**
     * @var list<string>
     */
    private const FIRST_NAMES = [
        'Juan', 'Carlos', 'María', 'José', 'Ana', 'Luis', 'Carmen', 'Jorge', 'Gloria', 'Pedro',
        'Luz', 'Julio', 'Rosa', 'Nelson', 'Dora', 'Mario', 'Sandra', 'Fernando', 'Gustavo', 'Yolanda',
        'Álvaro', 'Patricia', 'Hernán', 'Gladys', 'Óscar', 'Esperanza', 'Alberto', 'Olga', 'Wilson', 'Martha',
        'William', 'Ángela', 'Gabriel', 'Claudia', 'Humberto', 'Isabel', 'Edgar', 'Cecilia', 'Jairo', 'Beatriz',
        'Ramón', 'Amanda', 'Rubén', 'Bertha', 'Darío', 'Nubia', 'Gilberto', 'Mónica', 'Raúl', 'Ofelia',
        'César', 'Doris', 'Francisco', 'Amparo', 'Eduardo', 'Aura', 'Guillermo', 'Alba', 'Ricardo', 'Nelly',
        'Antonio', 'Leidy', 'Roberto', 'Carolina', 'Enrique', 'Yuri', 'Andrés', 'Paola', 'Mauricio', 'Diana',
        'Iván', 'Viviana', 'Arturo', 'Andrea', 'Camilo', 'Juliana', 'Fabio', 'Tatiana', 'Germán', 'Daniela',
        'Jorge Eliécer', 'Manuela', 'Hernando', 'Stefany', 'Miller', 'Katherine', 'Duván', 'Jhoana', 'Cristian', 'Valentina',
        'Sebastián', 'Laura', 'Santiago', 'Camila', 'Alejandro', 'Mariana', 'Nicolás', 'Natalia', 'Miguel', 'Isabella',
        'Mateo', 'Sara', 'Daniel', 'Angie', 'Brayan', 'Yineth', 'Danna', 'Kevin', 'Liseth', 'Óscar',
        'Elkin', 'Nayibe', 'Jackson', 'Mildred', 'Harold', 'Liliana', 'Jhon', 'Paula', 'Esneider', 'Lorena',
    ];

    /**
     * @var list<string>
     */
    private const LAST_NAMES = [
        'Rodríguez', 'García', 'Martínez', 'González', 'López', 'Hernández', 'Díaz', 'Muñoz', 'Sánchez', 'Pérez',
        'Torres', 'Rojas', 'Ramírez', 'Castro', 'Jiménez', 'Vargas', 'Gómez', 'Moreno', 'Salazar', 'Ruiz',
        'Valencia', 'Restrepo', 'Cárdenas', 'Mosquera', 'Arias', 'Osorio', 'Jaramillo', 'Gutiérrez', 'Zapata', 'Murillo',
        'Ospina', 'Londoño', 'Quintero', 'Ríos', 'Álvarez', 'Mesa', 'Arango', 'Betancur', 'Duque', 'Ochoa',
        'Piedrahíta', 'Uribe', 'Vélez', 'Zuluaga', 'Cardona', 'Montoya', 'Molina', 'Correa', 'Parra', 'Mora',
        'Peña', 'Rincón', 'Navarro', 'Ávila', 'Naranjo', 'Cruz', 'Vega', 'Serna', 'Marín', 'Castaño',
        'Márquez', 'Guerrero', 'Carvajal', 'Chaves', 'Beltrán', 'Cifuentes', 'Pardo', 'Henao', 'Villa', 'Suárez',
        'Escobar', 'Pineda', 'Manrique', 'Agudelo', 'Baena', 'Giraldo', 'Quiceno', 'Vanegas', 'Puerta', 'Hurtado',
        'Fernández', 'Ramos', 'Acero', 'Blanco', 'Burbano', 'Caicedo', 'Calvo', 'Cano', 'Contreras', 'Córdoba',
        'Cuéllar', 'Delgado', 'Espinoza', 'Fajardo', 'Flórez', 'Franco', 'Galvis', 'Gaviria', 'Hoyos', 'Lozano',
        'Nieto', 'Palacios', 'Prieto', 'Quintero', 'Sáenz', 'Tovar', 'Velásquez', 'Villegas', 'Zuleta', 'Amaya',
    ];

    /**
     * @var list<string>
     */
    private const NEIGHBORHOODS = [
        'Centro', 'San Fernando', 'La Candelaria', 'Kennedy', 'El Prado', 'Simón Bolívar', 'San Nicolás',
        'La Merced', 'Alfonso López', 'La Esperanza', 'El Bosque', 'Ciudad Jardín', 'Villa María', 'San Cayetano',
        'Las Américas', 'El Vergel', 'Bolívar', 'Siete de Agosto', 'Popular', 'San Benito',
    ];

    /**
     * @var list<string>
     */
    private const RELATIONSHIPS = ['Madre', 'Padre', 'Hijo', 'Hija', 'Cónyuge', 'Hermano', 'Hermana', 'Abuelo', 'Tío', 'Amigo'];

    private int $docCounter = 1000000000;

    /**
     * @var array<int, string>
     */
    private array $munDept = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->clear();

        [$sismoId, $severityPartialId, $severityTotalId] = $this->ensureIncidentType();
        $propertyIds = $this->ensurePropertyTypes();
        $causeIds = $this->ensureCasualtyCauses();

        [$munPool, $centroids, $munNames] = $this->loadGeography();

        $adminId = DB::table('users')->where('email', 'admin@acopio.test')->value('id');
        $orgId = DB::table('organizations')->orderBy('id')->value('id');
        $statusIds = DB::table('affectation_statuses')->pluck('id', 'code')->toArray();
        $needIds = DB::table('needs')->pluck('id', 'name')->toArray();

        $first = self::FIRST_NAMES;
        $last = self::LAST_NAMES;

        // --- Viviendas (163.896): 29.554 destruidas + 134.342 averiadas ---
        [$destroyedPool, $injuredPool, $destroyedAffIds, $viviendaRange] = $this->seedViviendas(
            $munPool,
            $centroids,
            $sismoId,
            $severityPartialId,
            $severityTotalId,
            $propertyIds['vivienda'],
            $orgId,
            $adminId,
            $statusIds,
            $needIds,
            $first,
            $last,
        );

        // --- Edificios, colegios y hospitales ---
        $this->seedInfrastructure(
            $munPool,
            $centroids,
            $sismoId,
            $severityPartialId,
            $severityTotalId,
            $propertyIds,
            $orgId,
            $adminId,
            $statusIds,
            $first,
            $last,
        );

        // --- Víctimas ---
        $this->seedCasualties(
            $destroyedPool,
            $injuredPool,
            $destroyedAffIds,
            $viviendaRange,
            $causeIds,
        );

        // --- Reportes de búsqueda (desaparecidos y rescatados) ---
        $this->seedSearchReports($munPool, $munNames, $adminId, $first, $last);

        // --- Facilities: albergues y centros de acopio ---
        $this->seedFacilities($orgId, $munPool, $centroids, $munNames);

        $this->report($viviendaRange);
    }

    private function clear(): void
    {
        DB::table('casualties')->delete();
        DB::table('search_reports')->delete();
        DB::table('family_members')->delete();
        DB::table('affectation_need')->delete();
        DB::table('affectation_severity')->delete();
        DB::table('affectation_property_type')->delete();
        DB::table('attachments')->delete();
        DB::table('facilities')->delete();
        DB::table('affectations')->delete();
        DB::table('people')->delete();
    }

    /**
     * @return array{int, int, int} [incident_type_id, severity partial id, severity total id]
     */
    private function ensureIncidentType(): array
    {
        $type = IncidentType::query()->updateOrCreate(
            ['code' => 'sismo'],
            [
                'display_name' => 'Terremoto',
                'description' => 'Sismo de magnitud 7,4 con epicentro en San José del Palmar (Chocó), 10 de agosto de 2026.',
                'severity_mode' => 'single',
            ],
        );

        $partial = AffectationSeverity::query()->updateOrCreate(
            ['incident_type_id' => $type->id, 'code' => 'partial'],
            ['display_name' => 'Afectación parcial', 'description' => 'El predio presenta daños parciales', 'order' => 1],
        );

        $total = AffectationSeverity::query()->updateOrCreate(
            ['incident_type_id' => $type->id, 'code' => 'total'],
            ['display_name' => 'Afectación total', 'description' => 'El predio quedó destruido o inhabitado', 'order' => 2],
        );

        $derrumbes = IncidentType::query()->where('code', 'derrumbes')->first();
        if ($derrumbes !== null) {
            $needs = $derrumbes->needs()->pluck('needs.id');
            $type->needs()->sync($needs);
        }

        return [$type->id, $partial->id, $total->id];
    }

    /**
     * @return array<string, int>
     */
    private function ensurePropertyTypes(): array
    {
        $codes = ['vivienda', 'edificio', 'colegio', 'hospital'];
        $ids = [];

        foreach ($codes as $code) {
            $ids[$code] = PropertyType::query()->updateOrCreate(
                ['code' => $code],
                ['display_name' => PropertyType::query()->where('code', $code)->value('display_name') ?? ucfirst($code)],
            )->id;
        }

        return $ids;
    }

    /**
     * @return array<string, int>
     */
    private function ensureCasualtyCauses(): array
    {
        $causes = [
            'aplastamiento' => 'Por aplastamiento',
            'sismo' => 'Eventos relacionados con el sismo',
            'quemaduras' => 'Por quemaduras',
            'ahogamiento' => 'Por ahogamiento',
            'otros' => 'Otras causas',
        ];

        $ids = [];

        foreach ($causes as $code => $displayName) {
            $ids[$code] = CasualtyCause::query()->updateOrCreate(
                ['code' => $code],
                ['display_name' => $displayName],
            )->id;
        }

        return $ids;
    }

    /**
     * @return array{0: list<int>, 1: array<int, array{lat: float, lng: float}>, 2: array<int, string>}
     */
    private function loadGeography(): array
    {
        $rows = DB::table('municipalities')
            ->select('id', 'code', 'name', DB::raw('ST_Y(centroid) AS lat'), DB::raw('ST_X(centroid) AS lng'))
            ->get();

        $byDept = [];
        $centroids = [];
        $names = [];

        foreach ($rows as $row) {
            $dept = substr($row->code, 0, 2);
            $byDept[$dept][] = $row;
            $centroids[$row->id] = ['lat' => (float) $row->lat, 'lng' => (float) $row->lng];
            $names[$row->id] = $row->name;
            $this->munDept[$row->id] = $dept;
        }

        $pool = [];

        foreach (self::DEPARTMENTS as $code => $config) {
            foreach ($byDept[$code] ?? [] as $mun) {
                $weight = 1 + ($config['bonus'][$mun->name] ?? 0);
                $count = max(1, (int) round($weight * $config['weight']));

                for ($i = 0; $i < $count; $i++) {
                    $pool[] = $mun->id;
                }
            }
        }

        return [$pool, $centroids, $names];
    }

    /**
     * @param  list<int>  $munPool
     * @param  array<int, array{lat: float, lng: float}>  $centroids
     * @param  array<string, int>  $statusIds
     * @param  array<string, int>  $needIds
     * @param  list<string>  $first
     * @param  list<string>  $last
     * @return array{0: list<int>, 1: list<int>, 2: list<int>, 3: array{int, int}}
     */
    private function seedViviendas(
        array $munPool,
        array $centroids,
        int $sismoId,
        int $severityPartialId,
        int $severityTotalId,
        int $viviendaPropertyId,
        ?int $orgId,
        ?int $adminId,
        array $statusIds,
        array $needIds,
        array $first,
        array $last,
    ): array {
        $destroyedPool = [];
        $injuredPool = [];
        $destroyedAffIds = [];
        $firstAffId = null;
        $lastAffId = null;

        $destroyedNeedPool = $this->needPool($needIds, ['Albergue', 'Dónde dormir', 'Reconstrucción', 'Comida', 'Kit de aseo', 'Ropa']);
        $damagedNeedPool = $this->needPool($needIds, ['Comida', 'Ropa', 'Kit de aseo', 'Transporte', 'Atención médica', 'Medicamentos']);

        $total = self::DESTROYED_VILLAS + self::DAMAGED_VILLAS;
        $done = 0;

        while ($done < $total) {
            $count = min(self::CHUNK, $total - $done);

            $peopleRows = [];
            $meta = [];
            $offset = 0;

            for ($i = 0; $i < $count; $i++) {
                $munId = $munPool[array_rand($munPool)];
                $base = $centroids[$munId];
                $lat = $base['lat'] + mt_rand(-200, 200) / 10000;
                $lng = $base['lng'] + mt_rand(-200, 200) / 10000;
                $severity = $done + $i < self::DESTROYED_VILLAS ? 'total' : 'partial';
                $familySize = $this->familySize();

                for ($m = 0; $m < $familySize; $m++) {
                    $peopleRows[] = $this->personRow($munId, $lat, $lng, $adminId, $first, $last, $m === 0);
                }

                $meta[] = [
                    'familySize' => $familySize,
                    'offset' => $offset,
                    'severity' => $severity,
                    'lat' => $lat,
                    'lng' => $lng,
                ];

                $offset += $familySize;
            }

            [$peopleStart] = $this->insertRange('people', $peopleRows);

            $affectationRows = [];
            $cursor = $peopleStart;

            foreach ($meta as $metaRow) {
                $affectationRows[] = $this->affectationRow(
                    $cursor,
                    $metaRow['severity'] === 'total',
                    $sismoId,
                    $orgId,
                    $adminId,
                    $statusIds,
                    $metaRow['lat'],
                    $metaRow['lng'],
                );

                if ($metaRow['severity'] === 'total') {
                    for ($k = 0; $k < $metaRow['familySize']; $k++) {
                        $destroyedPool[] = $cursor + $k;
                        $injuredPool[] = $cursor + $k;
                    }
                } else {
                    for ($k = 0; $k < $metaRow['familySize']; $k++) {
                        $injuredPool[] = $cursor + $k;
                    }
                }

                $cursor += $metaRow['familySize'];
            }

            [$affStart, $affEnd] = $this->insertRange('affectations', $affectationRows);

            if ($firstAffId === null) {
                $firstAffId = $affStart;
            }
            $lastAffId = $affEnd;

            $now = $this->now();

            $severityRows = [];
            $propertyRows = [];
            $familyRows = [];
            $needRows = [];
            $cursor = $peopleStart;

            foreach ($meta as $j => $metaRow) {
                $affId = $affStart + $j;

                $severityRows[] = [
                    'affectation_id' => $affId,
                    'affectation_severity_id' => $metaRow['severity'] === 'total' ? $severityTotalId : $severityPartialId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $propertyRows[] = [
                    'affectation_id' => $affId,
                    'property_type_id' => $viviendaPropertyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                for ($k = 0; $k < $metaRow['familySize']; $k++) {
                    $familyRows[] = [
                        'affectation_id' => $affId,
                        'person_id' => $cursor + $k,
                        'is_householder' => $k === 0,
                        'family_group' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $needPool = $metaRow['severity'] === 'total' ? $destroyedNeedPool : $damagedNeedPool;
                $needCount = $metaRow['severity'] === 'total' ? mt_rand(2, 3) : mt_rand(1, 2);

                if ($needPool !== []) {
                    shuffle($needPool);
                    for ($x = 0; $x < min($needCount, count($needPool)); $x++) {
                        $needRows[] = [
                            'affectation_id' => $affId,
                            'need_id' => $needPool[$x],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($metaRow['severity'] === 'total') {
                    $destroyedAffIds[] = $affId;
                }

                $cursor += $metaRow['familySize'];
            }

            DB::table('affectation_severity')->insert($severityRows);
            DB::table('affectation_property_type')->insert($propertyRows);
            DB::table('family_members')->insert($familyRows);
            DB::table('affectation_need')->insert($needRows);

            $done += $count;
            $this->progress("Viviendas: {$done}/{$total}");
        }

        return [$destroyedPool, $injuredPool, $destroyedAffIds, [$firstAffId ?? 0, $lastAffId ?? 0]];
    }

    /**
     * @param  list<int>  $munPool
     * @param  array<int, array{lat: float, lng: float}>  $centroids
     * @param  array<string, int>  $statusIds
     * @param  array<string, int>  $propertyIds
     * @param  list<string>  $first
     * @param  list<string>  $last
     */
    private function seedInfrastructure(
        array $munPool,
        array $centroids,
        int $sismoId,
        int $severityPartialId,
        int $severityTotalId,
        array $propertyIds,
        ?int $orgId,
        ?int $adminId,
        array $statusIds,
        array $first,
        array $last,
    ): void {
        $categories = [
            ['count' => self::COLLAPSED_BUILDINGS, 'property' => 'edificio', 'severity' => 'total', 'severityId' => $severityTotalId],
            ['count' => self::AFFECTED_SCHOOLS, 'property' => 'colegio', 'severity' => 'partial', 'severityId' => $severityPartialId],
            ['count' => self::AFFECTED_HOSPITALS, 'property' => 'hospital', 'severity' => 'partial', 'severityId' => $severityPartialId],
        ];

        foreach ($categories as $category) {
            $total = $category['count'];
            $done = 0;

            while ($done < $total) {
                $count = min(self::CHUNK, $total - $done);

                $peopleRows = [];
                $meta = [];

                for ($i = 0; $i < $count; $i++) {
                    $munId = $munPool[array_rand($munPool)];
                    $base = $centroids[$munId];
                    $lat = $base['lat'] + mt_rand(-200, 200) / 10000;
                    $lng = $base['lng'] + mt_rand(-200, 200) / 10000;

                    $peopleRows[] = $this->personRow($munId, $lat, $lng, $adminId, $first, $last, true);
                    $meta[] = ['lat' => $lat, 'lng' => $lng];
                }

                [$peopleStart] = $this->insertRange('people', $peopleRows);

                $affectationRows = [];

                foreach ($meta as $i => $metaRow) {
                    $affectationRows[] = $this->affectationRow(
                        $peopleStart + $i,
                        $category['severity'] === 'total',
                        $sismoId,
                        $orgId,
                        $adminId,
                        $statusIds,
                        $metaRow['lat'],
                        $metaRow['lng'],
                    );
                }

                [$affStart] = $this->insertRange('affectations', $affectationRows);

                $now = $this->now();

                $severityRows = [];
                $propertyRows = [];

                for ($i = 0; $i < $count; $i++) {
                    $affId = $affStart + $i;

                    $severityRows[] = [
                        'affectation_id' => $affId,
                        'affectation_severity_id' => $category['severityId'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $propertyRows[] = [
                        'affectation_id' => $affId,
                        'property_type_id' => $propertyIds[$category['property']],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('affectation_severity')->insert($severityRows);
                DB::table('affectation_property_type')->insert($propertyRows);

                $done += $count;
                $this->progress("{$category['property']}: {$done}/{$total}");
            }
        }
    }

    /**
     * @param  list<int>  $destroyedPool
     * @param  list<int>  $injuredPool
     * @param  list<int>  $destroyedAffIds
     * @param  array{int, int}  $viviendaRange
     * @param  array<string, int>  $causeIds
     */
    private function seedCasualties(array $destroyedPool, array $injuredPool, array $destroyedAffIds, array $viviendaRange, array $causeIds): void
    {
        $now = $this->now();
        $rows = [];

        $deceasedIds = array_rand($destroyedPool, min(self::DECEASED, count($destroyedPool)));
        $deceasedIds = is_array($deceasedIds) ? $deceasedIds : [$deceasedIds];

        foreach ($deceasedIds as $key) {
            $rows[] = [
                'affectation_id' => $destroyedAffIds[array_rand($destroyedAffIds)],
                'person_id' => $destroyedPool[$key],
                'type' => 'deceased',
                'cause_id' => $causeIds[$this->causeCode()],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $injuredKeys = array_rand($injuredPool, min(self::INJURED, count($injuredPool)));
        $injuredKeys = is_array($injuredKeys) ? $injuredKeys : [$injuredKeys];

        foreach ($injuredKeys as $key) {
            $rows[] = [
                'affectation_id' => mt_rand($viviendaRange[0], $viviendaRange[1]),
                'person_id' => $injuredPool[$key],
                'type' => 'injured',
                'cause_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 2000) as $chunk) {
            DB::table('casualties')->insert($chunk);
        }

        $this->progress('Víctimas: '.count($rows));
    }

    /**
     * @param  list<int>  $munPool
     * @param  array<int, string>  $munNames
     * @param  list<string>  $first
     * @param  list<string>  $last
     */
    private function seedSearchReports(array $munPool, array $munNames, ?int $adminId, array $first, array $last): void
    {
        $rows = [];
        $now = $this->now();

        $build = function (string $status, ?string $locatedAt) use (&$rows, $munPool, $munNames, $adminId, $first, $last, $now): void {
            $rows[] = [
                'person_id' => null,
                'searched_name' => trim($first[array_rand($first)].' '.$last[array_rand($last)]),
                'document_type' => 'CC',
                'document_number' => (string) mt_rand(10000000, 99999999),
                'municipality' => $munNames[$munPool[array_rand($munPool)]],
                'reporter_name' => trim($first[array_rand($first)].' '.$last[array_rand($last)]),
                'reporter_phone' => '57'.mt_rand(3000000000, 3999999999),
                'relationship' => self::RELATIONSHIPS[array_rand(self::RELATIONSHIPS)],
                'status' => $status,
                'notes' => null,
                'handled_by' => $adminId,
                'located_at' => $locatedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        for ($i = 0; $i < self::MISSING_REPORTS; $i++) {
            $build('pending', null);
        }

        for ($i = 0; $i < self::FOUND_REPORTS; $i++) {
            $build('found', $this->ts());
        }

        foreach (array_chunk($rows, 2000) as $chunk) {
            DB::table('search_reports')->insert($chunk);
        }

        $this->progress('Reportes de búsqueda: '.count($rows));
    }

    /**
     * @param  list<int>  $munPool
     * @param  array<int, array{lat: float, lng: float}>  $centroids
     * @param  array<int, string>  $munNames
     */
    private function seedFacilities(?int $orgId, array $munPool, array $centroids, array $munNames): void
    {
        $facilityTypes = DB::table('facility_types')->pluck('id', 'code')->toArray();
        $rows = [];

        $build = function (string $code, int $count, string $prefix) use (&$rows, $orgId, $facilityTypes, $munPool, $centroids, $munNames): void {
            for ($i = 0; $i < $count; $i++) {
                $munId = $munPool[array_rand($munPool)];
                $base = $centroids[$munId];
                $capacity = mt_rand(50, 500);
                $available = (int) round($capacity * mt_rand(60, 95) / 100);

                $rows[] = [
                    'organization_id' => $orgId,
                    'facility_type_id' => $facilityTypes[$code],
                    'municipality_id' => $munId,
                    'name' => $prefix.' '.$munNames[$munId].' '.($i + 1),
                    'address' => $this->address(),
                    'latitude' => $base['lat'] + mt_rand(-200, 200) / 10000,
                    'longitude' => $base['lng'] + mt_rand(-200, 200) / 10000,
                    'capacity' => $capacity,
                    'available' => $available,
                    'description' => $code === 'albergue'
                        ? 'Alojamiento temporal para personas afectadas por el sismo.'
                        : 'Punto de recepción y distribución de donaciones.',
                    'status' => 'operational',
                    'contact_phone' => '57'.mt_rand(3000000000, 3999999999),
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ];
            }
        };

        $build('albergue', self::SHELTERS, 'Albergue');
        $build('acopio', self::COLLECTION_CENTERS, 'Centro de acopio');

        foreach (array_chunk($rows, 2000) as $chunk) {
            DB::table('facilities')->insert($chunk);
        }

        $this->progress('Facilities: '.count($rows));
    }

    /**
     * @param  array<string, int>  $needIds
     * @param  list<string>  $names
     * @return list<int>
     */
    private function needPool(array $needIds, array $names): array
    {
        $pool = [];

        foreach ($names as $name) {
            if (isset($needIds[$name])) {
                $pool[] = $needIds[$name];
            }
        }

        return $pool;
    }

    /**
     * @param  list<string>  $first
     * @param  list<string>  $last
     * @return array<string, mixed>
     */
    private function personRow(int $munId, float $lat, float $lng, ?int $adminId, array $first, array $last, bool $forceVerified): array
    {
        $roll = mt_rand(0, 99);
        $status = $roll < 60 ? 'verified' : ($roll < 85 ? 'located' : 'registered');

        if ($forceVerified) {
            $status = 'verified';
        }

        $verifiedAt = $status === 'verified' || $status === 'located' ? $this->ts() : null;
        $locatedAt = $status === 'located' ? $this->ts() : null;

        $age = $this->age();
        $birthDate = mt_rand(0, 99) < 75
            ? date('Y-m-d', strtotime("-{$age} years", mt_rand(self::T0, self::T1)))
            : null;

        $location = mt_rand(0, 99) < 75
            ? DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)")
            : null;

        return [
            'document_type' => $this->documentType($age),
            'document_number' => (string) $this->docCounter++,
            'first_name' => $first[array_rand($first)],
            'last_name' => $last[array_rand($last)],
            'phone' => '57'.mt_rand(3000000000, 3999999999),
            'neighborhood' => mt_rand(0, 99) < 70 ? self::NEIGHBORHOODS[array_rand(self::NEIGHBORHOODS)] : null,
            'address' => mt_rand(0, 99) < 70 ? $this->address() : null,
            'sector' => $this->sector($munId),
            'municipality_id' => $munId,
            'location' => $location,
            'status' => $status,
            'special_needs' => mt_rand(0, 99) < 5
                ? '["'.(['Discapacidad movilidad', 'Embarazada', 'Enfermedad crónica'])[mt_rand(0, 2)].'"]'
                : '[]',
            'source' => 'web',
            'data_consent' => true,
            'verified_by' => $verifiedAt !== null ? $adminId : null,
            'verified_at' => $verifiedAt,
            'located_at' => $locatedAt,
            'birth_date' => $birthDate,
            'current_age' => $age,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
    }

    /**
     * @param  array<string, int>  $statusIds
     * @return array<string, mixed>
     */
    private function affectationRow(
        int $personId,
        bool $destroyed,
        int $sismoId,
        ?int $orgId,
        ?int $adminId,
        array $statusIds,
        float $lat,
        float $lng,
    ): array {
        $roll = mt_rand(0, 99);
        $status = $roll < 60 ? 'verified' : ($roll < 85 ? 'located' : 'reported');

        $verifiedAt = $status === 'verified' || $status === 'located' ? $this->ts() : null;
        $locatedAt = $status === 'located' ? $this->ts() : null;

        return [
            'person_id' => $personId,
            'incident_type_id' => $sismoId,
            'reported_by' => $adminId,
            'organization_id' => $orgId,
            'status_id' => $statusIds[$status],
            'address' => $this->address(),
            'description' => $destroyed
                ? 'Vivienda destruida por el sismo del 10 de agosto de 2026.'
                : 'Vivienda con afectación parcial por el sismo del 10 de agosto de 2026.',
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)"),
            'verified_by' => $verifiedAt !== null ? $adminId : null,
            'verified_at' => $verifiedAt,
            'located_at' => $locatedAt,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
    }

    private function familySize(): int
    {
        $roll = mt_rand(0, 99);

        return match (true) {
            $roll < 41 => 1,
            $roll < 85 => 2,
            $roll < 96 => 3,
            default => 4,
        };
    }

    private function age(): int
    {
        $roll = mt_rand(0, 99);

        return match (true) {
            $roll < 8 => mt_rand(0, 17),
            $roll < 28 => mt_rand(18, 24),
            $roll < 92 => mt_rand(25, 60),
            default => mt_rand(61, 95),
        };
    }

    private function documentType(int $age): string
    {
        if ($age < 18 && mt_rand(0, 99) < 90) {
            return 'TI';
        }

        $roll = mt_rand(0, 99);

        return match (true) {
            $roll < 80 => 'CC',
            $roll < 95 => 'CE',
            $roll < 97 => 'PA',
            default => 'OTHER',
        };
    }

    private function sector(int $munId): string
    {
        $dept = $this->munDept[$munId] ?? '';

        $ruralBias = in_array($dept, ['27', '19', '52'], true) ? 45 : 30;

        return mt_rand(0, 99) < $ruralBias ? 'rural' : 'urban';
    }

    private function causeCode(): string
    {
        $roll = mt_rand(0, 99);

        return match (true) {
            $roll < 60 => 'aplastamiento',
            $roll < 85 => 'sismo',
            $roll < 91 => 'quemaduras',
            $roll < 95 => 'ahogamiento',
            default => 'otros',
        };
    }

    private function address(): string
    {
        $street = ['Calle', 'Carrera', 'Diagonal', 'Transversal'];

        return $street[array_rand($street)]
            .' '.mt_rand(1, 120)
            .' # '.mt_rand(1, 60)
            .' - '.mt_rand(1, 40);
    }

    private function now(): string
    {
        return $this->ts();
    }

    private function ts(): string
    {
        return date('Y-m-d H:i:s', mt_rand(self::T0, self::T1));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{int, int}
     */
    private function insertRange(string $table, array $rows): array
    {
        if ($rows === []) {
            return [0, -1];
        }

        $base = (int) DB::table($table)->max('id');
        $sequence = DB::selectOne(
            "SELECT pg_get_serial_sequence('{$table}', 'id') AS seq"
        )?->seq;

        if ($sequence !== null) {
            if ($base > 0) {
                DB::statement("SELECT setval('{$sequence}', {$base}, true)");
            } else {
                DB::statement("SELECT setval('{$sequence}', 1, false)");
            }
        }

        $columns = count($rows[0]);
        $batch = max(1, intdiv(self::MAX_PARAMS, $columns));

        foreach (array_chunk($rows, $batch) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        $start = $base + 1;

        return [$start, $start + count($rows) - 1];
    }

    /**
     * @param  array{int, int}  $viviendaRange
     */
    private function report(array $viviendaRange): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->newLine();
        $this->command->info('=== Resumen Terremoto 2026 ===');
        $this->command->info('Personas: '.number_format(DB::table('people')->count()));
        $this->command->info('Afectaciones: '.number_format(DB::table('affectations')->count()));
        $this->command->info('Viviendas: '.number_format($viviendaRange[1] - $viviendaRange[0] + 1));
        $this->command->info('Miembros de familia: '.number_format(DB::table('family_members')->count()));
        $this->command->info('Fallecidos: '.DB::table('casualties')->where('type', 'deceased')->count());
        $this->command->info('Lesionados: '.DB::table('casualties')->where('type', 'injured')->count());
        $this->command->info('Reportes de búsqueda: '.DB::table('search_reports')->count());
        $this->command->info('Facilities: '.DB::table('facilities')->count());
    }

    private function progress(string $message): void
    {
        if ($this->command !== null) {
            $this->command->info("  > {$message}");
        }
    }
}
