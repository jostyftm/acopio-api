<?php

namespace App\Services\Affectation;

use App\Models\Affectation;
use App\Models\AffectationStatus;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;

class AffectationReportService
{
    /**
     * Registers an incident report without an associated person.
     *
     * The affectation is created with the `reported` status so it can be
     * verified and located later by the organization that covers the zone.
     *
     * @param  array<string, mixed>  $data
     */
    public function report(array $data, User $reporter): Affectation
    {
        $latitude = isset($data['incident_latitude']) ? (float) $data['incident_latitude'] : null;
        $longitude = isset($data['incident_longitude']) ? (float) $data['incident_longitude'] : null;

        $reportedStatus = AffectationStatus::query()->where('code', 'reported')->firstOrFail();

        $affectation = Affectation::query()->create([
            'incident_type_id' => $data['incident_type_id'],
            'reported_by' => $reporter->id,
            'organization_id' => $reporter->organization_id,
            'status_id' => $reportedStatus->id,
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'location' => $this->pointFrom($latitude, $longitude),
        ]);

        $affectation->propertyTypes()->attach($data['property_types']);

        if (! empty($data['severities'])) {
            $affectation->severities()->attach($data['severities']);
        }

        if (! empty($data['needs'])) {
            $affectation->needs()->attach($data['needs']);
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

        return $affectation;
    }

    private function pointFrom(?float $latitude, ?float $longitude): ?Point
    {
        return $latitude !== null && $longitude !== null
            ? Point::makeGeodetic($latitude, $longitude)
            : null;
    }
}
