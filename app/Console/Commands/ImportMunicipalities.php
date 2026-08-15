<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportMunicipalities extends Command
{
    protected $signature = 'municipalities:import
                            {--file= : Ruta al GeoJSON de municipios (por defecto storage/app/geo/colombia-municipios.geojson)}
                            {--backfill-people : Re-geocodifica las personas con ubicación para asignarles municipio}';

    protected $description = 'Importa el catálogo de departamentos y municipios de Colombia (DIVIPOLA) desde un GeoJSON';

    public function handle(): int
    {
        $file = $this->option('file') ?? storage_path('app/geo/colombia-municipios.geojson');

        if (! is_file($file)) {
            $this->error("No se encontró el archivo GeoJSON: {$file}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data) || ($data['type'] ?? null) !== 'FeatureCollection') {
            $this->error('El archivo no es un GeoJSON FeatureCollection válido.');

            return self::FAILURE;
        }

        $features = $data['features'] ?? [];
        $departments = $this->collectDepartments($features);
        $importedDepartments = $this->upsertDepartments($departments);

        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($features, &$imported, &$updated): void {
            foreach ($features as $feature) {
                $properties = $feature['properties'] ?? [];
                $geometry = $feature['geometry'] ?? null;

                if ($geometry === null || ! isset($properties['MPIO_CCNCT'])) {
                    continue;
                }

                $name = (string) $properties['MPIO_CNMBR'];
                $geometryJson = json_encode($geometry);

                $result = DB::selectOne(
                    <<<'SQL'
                    INSERT INTO municipalities
                        (department_id, code, name, normalized_name, centroid, boundary, created_at, updated_at)
                    VALUES
                        ((SELECT id FROM departments WHERE code = ?), ?, ?, ?, ST_Centroid(ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))), ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)), NOW(), NOW())
                    ON CONFLICT (code) DO UPDATE SET
                        department_id = EXCLUDED.department_id,
                        name = EXCLUDED.name,
                        normalized_name = EXCLUDED.normalized_name,
                        centroid = EXCLUDED.centroid,
                        boundary = EXCLUDED.boundary,
                        updated_at = NOW()
                    RETURNING (CASE WHEN xmax = 0 THEN 1 ELSE 0 END) AS inserted
                    SQL,
                    [
                        (string) $properties['DPTO_CCDGO'],
                        (string) $properties['MPIO_CCNCT'],
                        $name,
                        Str::upper(iconv('UTF-8', 'ASCII//TRANSLIT', $name)),
                        $geometryJson,
                        $geometryJson,
                    ],
                );

                $result?->inserted ? $imported++ : $updated++;
            }
        });

        $this->refreshDepartmentCentroids();

        $this->info("Departamentos importados: {$importedDepartments}.");
        $this->info("Municipios importados: {$imported} nuevos, {$updated} actualizados.");

        if ($this->option('backfill-people')) {
            $this->backfillPeople();
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $features
     * @return array<string, array{code: string, name: string, normalized_name: string}>
     */
    private function collectDepartments(array $features): array
    {
        $departments = [];

        foreach ($features as $feature) {
            $properties = $feature['properties'] ?? [];

            if (! isset($properties['DPTO_CCDGO'])) {
                continue;
            }

            $code = (string) $properties['DPTO_CCDGO'];
            $name = (string) $properties['DPTO_CNMBR'];

            $departments[$code] = [
                'code' => $code,
                'name' => $name,
                'normalized_name' => Str::upper(iconv('UTF-8', 'ASCII//TRANSLIT', $name)),
            ];
        }

        return $departments;
    }

    /**
     * @param  array<string, array{code: string, name: string, normalized_name: string}>  $departments
     */
    private function upsertDepartments(array $departments): int
    {
        $count = 0;

        DB::transaction(function () use ($departments, &$count): void {
            foreach ($departments as $department) {
                DB::statement(
                    <<<'SQL'
                    INSERT INTO departments (code, name, normalized_name, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                    ON CONFLICT (code) DO UPDATE SET
                        name = EXCLUDED.name,
                        normalized_name = EXCLUDED.normalized_name,
                        updated_at = NOW()
                    SQL,
                    [$department['code'], $department['name'], $department['normalized_name']],
                );

                $count++;
            }
        });

        return $count;
    }

    private function refreshDepartmentCentroids(): void
    {
        DB::statement(
            <<<'SQL'
            UPDATE departments d
            SET centroid = m.centroid,
                updated_at = NOW()
            FROM (
                SELECT department_id, ST_Centroid(ST_Union(boundary)) AS centroid
                FROM municipalities
                GROUP BY department_id
            ) AS m
            WHERE m.department_id = d.id
            SQL
        );
    }

    private function backfillPeople(): void
    {
        $affected = DB::update(
            <<<'SQL'
            UPDATE people p
            SET municipality_id = m.id,
                updated_at = NOW()
            FROM municipalities m
            WHERE p.location IS NOT NULL
              AND ST_Covers(m.boundary, p.location)
            SQL
        );

        $this->info("Personas re-geocodificadas: {$affected}.");
    }
}
