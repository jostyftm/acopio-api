<?php

namespace Database\Seeders;

use App\Enums\CasualtyType;
use App\Models\Affectation;
use App\Models\AffectationSeverity;
use App\Models\Casualty;
use App\Models\CasualtyCause;
use App\Models\FamilyMember;
use App\Models\IncidentType;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->clearPreviousDemoData();

            $organization = Organization::query()->first();

            $severities = $this->ensureDerrumbesSeverities();

            $casualtiesByCause = [
                'aplastamiento' => 11,
                'sismo' => 15,
            ];
            $injuredTotal = 120;
            $destroyedTotal = 18;
            $damagedTotal = 40;

            $affectations = [];

            // Viviendas destruidas (severidad total).
            for ($i = 0; $i < $destroyedTotal; $i++) {
                $affectations[] = $this->createAffectation($severities['incident_type_id'], $severities['total_severity_id'], $organization?->id);
            }

            // Viviendas averiadas (severidad parcial).
            for ($i = 0; $i < $damagedTotal; $i++) {
                $affectations[] = $this->createAffectation($severities['incident_type_id'], $severities['partial_severity_id'], $organization?->id);
            }

            // Familias afectadas: algunas afectaciones tienen más de un grupo familiar.
            foreach ($affectations as $index => $affectation) {
                $this->seedFamilyGroups($affectation, $index % 5 === 0 ? 2 : 1);
            }

            // Fallecidos por causa específica.
            foreach ($casualtiesByCause as $code => $total) {
                $cause = CasualtyCause::query()->where('code', $code)->firstOrFail();
                for ($i = 0; $i < $total; $i++) {
                    $this->createCasualty($affectations[array_rand($affectations)], $cause, CasualtyType::Deceased);
                }
            }

            // Personas lesionadas.
            for ($i = 0; $i < $injuredTotal; $i++) {
                $this->createCasualty($affectations[array_rand($affectations)], null, CasualtyType::Injured);
            }
        });
    }

    /**
     * Limpia los datos generados por una ejecución previa del seeder.
     */
    private function clearPreviousDemoData(): void
    {
        Casualty::query()->delete();
        FamilyMember::query()->delete();
        Affectation::query()->delete();

        Person::query()
            ->whereDoesntHave('affectation')
            ->whereDoesntHave('familyMembers')
            ->whereNotIn('id', fn ($query) => $query->select('person_id')->from('casualties')->whereNotNull('person_id'))
            ->delete();
    }

    /**
     * Crea (idempotente) el tipo de incidente "derrumbes" con sus gravedades.
     *
     * @return array{incident_type_id: int, partial_severity_id: int, total_severity_id: int}
     */
    private function ensureDerrumbesSeverities(): array
    {
        $type = IncidentType::query()->firstOrCreate(
            ['code' => 'derrumbes'],
            ['display_name' => 'Derrumbes', 'severity_mode' => 'single'],
        );

        $partial = AffectationSeverity::query()->firstOrCreate(
            ['incident_type_id' => $type->id, 'code' => 'partial'],
            ['display_name' => 'Afectación parcial', 'order' => 1],
        );

        $total = AffectationSeverity::query()->firstOrCreate(
            ['incident_type_id' => $type->id, 'code' => 'total'],
            ['display_name' => 'Afectación total', 'order' => 2],
        );

        return [
            'incident_type_id' => $type->id,
            'partial_severity_id' => $partial->id,
            'total_severity_id' => $total->id,
        ];
    }

    private function createAffectation(int $incidentTypeId, int $severityId, ?int $organizationId): Affectation
    {
        $householder = Person::factory()->create();
        $affectation = $householder->affectation()->create([
            'incident_type_id' => $incidentTypeId,
            'organization_id' => $organizationId,
            'description' => 'Vivienda afectada por el evento.',
        ]);
        $affectation->severities()->attach($severityId);

        return $affectation;
    }

    private function seedFamilyGroups(Affectation $affectation, int $groupCount): void
    {
        for ($group = 1; $group <= $groupCount; $group++) {
            $householderId = $group === 1 ? $affectation->person_id : Person::factory()->create()->id;

            $affectation->familyMembers()->create([
                'person_id' => $householderId,
                'is_householder' => true,
                'family_group' => $group,
            ]);

            $extraMembers = rand(1, 3);
            for ($i = 0; $i < $extraMembers; $i++) {
                $affectation->familyMembers()->create([
                    'person_id' => Person::factory()->create()->id,
                    'is_householder' => false,
                    'family_group' => $group,
                ]);
            }
        }
    }

    private function createCasualty(Affectation $affectation, ?CasualtyCause $cause, CasualtyType $type): void
    {
        Casualty::query()->create([
            'affectation_id' => $affectation->id,
            'person_id' => $type === CasualtyType::Deceased ? null : Person::factory()->create()->id,
            'type' => $type,
            'cause_id' => $cause?->id,
        ]);
    }
}
