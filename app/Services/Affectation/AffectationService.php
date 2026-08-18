<?php

namespace App\Services\Affectation;

use App\Models\Affectation;
use App\Models\AffectationStatus;
use App\Models\Attachment;
use App\Models\User;
use App\Services\Person\PersonService;
use App\Support\Enums\SpatieRole;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AffectationService
{
    public function __construct(
        private readonly PersonService $personService,
    ) {}

    /**
     * Build the query for listing affectations, scoped by the user's role.
     *
     * Admin users see all affectations; non-admin users see only those
     * belonging to their organization or reported by them.
     *
     * @return Builder<Affectation>
     */
    public function indexQuery(User $user): Builder
    {
        $query = Affectation::query()
            ->with([
                'person.municipality', 'needs.severityNeed', 'incidentType', 'severities',
                'propertyTypes', 'reporter', 'organization', 'status', 'verifiedBy',
                'attachments', 'familyMembers.person.municipality', 'familyMembers.person.attachments',
            ])
            ->latest('id');

        if (! $user->hasRole(SpatieRole::Admin->value, 'api')) {
            $query->where(function (Builder $builder) use ($user): void {
                if ($user->organization_id !== null) {
                    $builder->where('organization_id', $user->organization_id);
                }

                $builder->orWhere('reported_by', $user->id);
            });
        }

        return $query;
    }

    /**
     * Eager-load all relationships needed for the affectation detail view.
     */
    public function loadRelations(Affectation $affectation): Affectation
    {
        return $affectation->load([
            'person.municipality', 'needs.severityNeed', 'incidentType', 'severities',
            'propertyTypes', 'attachments', 'reporter', 'organization',
            'status', 'verifiedBy', 'familyMembers.person.municipality', 'familyMembers.person.attachments',
        ]);
    }

    /**
     * Verify and optionally locate an affectation.
     *
     * When coordinates are provided, the affectation is marked as located
     * and the associated person (if any) is also verified/located.
     * All database operations are wrapped in a transaction.
     *
     * @param  array{latitude?: float, longitude?: float}  $data
     */
    public function verify(Affectation $affectation, User $verifier, array $data): Affectation
    {
        $hasLocation = isset($data['latitude'], $data['longitude']);
        $statusCode = $hasLocation ? 'located' : 'verified';

        return DB::transaction(function () use ($affectation, $verifier, $data, $hasLocation, $statusCode): Affectation {
            $status = AffectationStatus::query()->where('code', $statusCode)->firstOrFail();

            $affectation->update([
                'status_id' => $status->id,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'located_at' => $hasLocation ? now() : null,
            ]);

            if ($affectation->person !== null) {
                $person = $this->personService->verify($affectation->person, $verifier);

                if ($hasLocation) {
                    $this->personService->locate($person, [
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                    ]);
                }
            }

            if ($hasLocation) {
                $affectation->update([
                    'location' => Point::makeGeodetic(
                        (float) $data['latitude'],
                        (float) $data['longitude'],
                    ),
                ]);
            }

            return $this->loadRelations($affectation);
        });
    }

    /**
     * Update an affectation's data, relationships, and evidence files.
     *
     * Handles updating description, incident type, location geometry,
     * severities, property types, needs, and uploaded evidence files.
     * All operations are wrapped in a transaction.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $evidenceFiles
     */
    public function update(
        Affectation $affectation,
        array $data,
        array $evidenceFiles = [],
    ): Affectation {
        return DB::transaction(function () use ($affectation, $data, $evidenceFiles): Affectation {
            $payload = ['description' => $data['description'] ?? null];

            if (array_key_exists('incident_type_id', $data)) {
                $payload['incident_type_id'] = $data['incident_type_id'];
            }

            if (array_key_exists('latitude', $data) || array_key_exists('longitude', $data)) {
                $latitude = ! empty($data['latitude']) ? (float) $data['latitude'] : null;
                $longitude = ! empty($data['longitude']) ? (float) $data['longitude'] : null;

                $payload['location'] = $latitude !== null && $longitude !== null
                    ? Point::makeGeodetic($latitude, $longitude)
                    : null;
            }

            $affectation->update($payload);

            if (array_key_exists('severities', $data)) {
                $affectation->severities()->sync($data['severities'] ?? []);
            }

            if (array_key_exists('property_types', $data)) {
                $affectation->propertyTypes()->sync($data['property_types'] ?? []);
            }

            if (! empty($data['needs'])) {
                $affectation->needs()->sync($data['needs']);
            }

            $this->uploadEvidence($affectation, $evidenceFiles);

            return $this->loadRelations($affectation);
        });
    }

    /**
     * Delete an affectation and remove its evidence files from S3 storage.
     *
     * The associated person remains in the system.
     */
    public function destroy(Affectation $affectation): void
    {
        DB::transaction(function () use ($affectation): void {
            foreach ($affectation->attachments as $attachment) {
                Storage::disk('s3')->delete($attachment->file_path);
            }

            $affectation->delete();
        });
    }

    /**
     * Delete a single evidence attachment from an affectation.
     *
     * Removes the file from S3 and deletes the database record.
     *
     * @throws HttpException When the attachment
     *                       does not belong to the given affectation.
     */
    public function destroyEvidence(Affectation $affectation, Attachment $evidence): void
    {
        abort_if(
            $evidence->attachable_type !== Affectation::class
                || $evidence->attachable_id !== $affectation->id,
            404,
        );

        Storage::disk('s3')->delete($evidence->file_path);
        $evidence->delete();
    }

    /**
     * Upload evidence files and create attachment records for an affectation.
     *
     * @param  array<int, UploadedFile>  $files
     */
    private function uploadEvidence(Affectation $affectation, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('evidence/affectations/'.$affectation->id, 's3');

            $affectation->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}
