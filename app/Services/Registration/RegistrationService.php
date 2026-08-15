<?php

namespace App\Services\Registration;

use App\Enums\RegistrationSource;
use App\Models\Municipality;
use App\Models\Person;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Support\Str;

class RegistrationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Person
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
        ]);

        if ($person->wasRecentlyCreated && isset($data['severity'])) {
            $this->createAffectation($person, $data);
        }

        return $person;
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
    private function createAffectation(Person $person, array $data): void
    {
        $incidentLatitude = isset($data['incident_latitude']) ? (float) $data['incident_latitude'] : null;
        $incidentLongitude = isset($data['incident_longitude']) ? (float) $data['incident_longitude'] : null;

        $affectation = $person->affectation()->create([
            'severity' => $data['severity'],
            'description' => $data['description'] ?? null,
            'location' => $this->pointFrom($incidentLatitude, $incidentLongitude),
        ]);

        if (! empty($data['needs'])) {
            $affectation->needs()->attach($data['needs']);
        }

        foreach ($data['evidence'] ?? [] as $file) {
            $path = $file->store('evidence/affectations/'.$affectation->id, 's3');

            $affectation->evidence()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
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
