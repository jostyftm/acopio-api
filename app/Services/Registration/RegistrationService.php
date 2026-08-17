<?php

namespace App\Services\Registration;

use App\Enums\PersonStatus;
use App\Enums\RegistrationSource;
use App\Models\Affectation;
use App\Models\AffectationStatus;
use App\Models\Municipality;
use App\Models\Person;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Support\Str;

class RegistrationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, bool $markLocated = false, ?User $reporter = null): Person
    {
        $existing = $this->findDuplicate($data['document_type'], $data['document_number']);

        if ($existing !== null) {
            return $existing;
        }

        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;

        $data['location'] = Person::locationFromLatLng($latitude, $longitude);
        unset($data['latitude'], $data['longitude']);

        $municipalityId = $this->resolveMunicipalityId($latitude, $longitude, $data);
        unset($data['municipality'], $data['municipality_code']);

        if ($municipalityId !== null) {
            $data['municipality_id'] = $municipalityId;
        }

        $person = Person::query()->create([
            ...$data,
            'source' => $data['source'] ?? RegistrationSource::Web,
            'data_consent' => true,
            ...($markLocated ? [
                'status' => PersonStatus::Located,
                'verified_at' => now(),
                'located_at' => now(),
            ] : []),
        ]);

        if ($person->wasRecentlyCreated && isset($data['incident_type_id'])) {
            $this->createAffectation($person, $data, $reporter, $markLocated);
        }

        return $person->refresh();
    }

    /**
     * Resolves the municipality by coordinates, then by DIVIPOLA code, then by name.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveMunicipalityId(?float $latitude, ?float $longitude, array $data): ?int
    {
        $byCoordinates = Municipality::resolveForCoordinates($latitude, $longitude)['municipality_id'] ?? null;

        if ($byCoordinates !== null) {
            return $byCoordinates;
        }

        $code = isset($data['municipality_code']) ? trim((string) $data['municipality_code']) : '';

        if ($code !== '') {
            $municipality = Municipality::findByCode($code);

            if ($municipality !== null) {
                return $municipality->id;
            }
        }

        $name = isset($data['municipality']) ? trim((string) $data['municipality']) : '';

        if ($name !== '') {
            return Municipality::findByNormalizedName($name)?->id;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createAffectation(Person $person, array $data, ?User $reporter = null, bool $markLocated = false): void
    {
        $incidentLatitude = isset($data['incident_latitude']) ? (float) $data['incident_latitude'] : null;
        $incidentLongitude = isset($data['incident_longitude']) ? (float) $data['incident_longitude'] : null;

        $statusId = $this->affectationStatusIdFor($person, $markLocated);

        $affectation = $person->affectation()->create([
            'reported_by' => $reporter?->id,
            'organization_id' => $reporter?->organization_id,
            'incident_type_id' => $data['incident_type_id'],
            'status_id' => $statusId,
            'verified_by' => $markLocated ? $reporter?->id : null,
            'verified_at' => $markLocated ? now() : null,
            'located_at' => $markLocated ? now() : null,
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'location' => $this->pointFrom($incidentLatitude, $incidentLongitude),
        ]);

        if (! empty($data['needs'])) {
            $affectation->needs()->attach($data['needs']);
        }

        if (! empty($data['severities'])) {
            $affectation->severities()->attach($data['severities']);
        }

        if (! empty($data['property_types'])) {
            $affectation->propertyTypes()->attach($data['property_types']);
        }

        foreach ($data['evidence'] ?? [] as $file) {
            $path = $file->store('evidence/affectations/'.$affectation->id, 's3');

            $affectation->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        foreach ($data['families'] ?? [] as $familyIndex => $family) {
            $familyGroup = $familyIndex + 1;

            foreach ($family['members'] as $member) {
                $this->createFamilyMember($affectation, $member, $familyGroup);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $member
     */
    private function createFamilyMember(Affectation $affectation, array $member, int $familyGroup): void
    {
        $person = $this->findDuplicate($member['document_type'], $member['document_number']);

        if ($person === null) {
            $person = Person::query()->create([
                'document_type' => $member['document_type'],
                'document_number' => $member['document_number'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'birth_date' => $member['birth_date'] ?? null,
                'phone' => null,
                'source' => RegistrationSource::Family,
                'data_consent' => true,
            ]);
            $person->refresh();
        } elseif ($person->birth_date === null && ! empty($member['birth_date'])) {
            $person->update(['birth_date' => $member['birth_date']]);
            $person->refresh();
        }

        $affectation->familyMembers()->create([
            'person_id' => $person->id,
            'family_group' => $familyGroup,
            'is_householder' => $member['is_householder'] ?? false,
        ]);

        foreach ($member['evidence'] ?? [] as $file) {
            $path = $file->store('evidence/family-members/'.$person->id, 's3');

            $person->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function affectationStatusIdFor(Person $person, bool $markLocated): ?int
    {
        $code = match (true) {
            $markLocated => 'located',
            $person->status === PersonStatus::Verified => 'verified',
            default => 'reported',
        };

        return AffectationStatus::query()->where('code', $code)->value('id');
    }

    private function pointFrom(?float $latitude, ?float $longitude): ?Point
    {
        return $latitude !== null && $longitude !== null
            ? Point::makeGeodetic($latitude, $longitude)
            : null;
    }

    public function findDuplicate(string $documentType, string $documentNumber): ?Person
    {
        return Person::query()
            ->where('document_type', $documentType)
            ->where('document_number', Str::upper($documentNumber))
            ->first();
    }
}
