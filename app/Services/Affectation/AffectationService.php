<?php

namespace App\Services\Affectation;

use App\Enums\CasualtyType;
use App\Models\Affectation;
use App\Models\AffectationStatus;
use App\Models\Attachment;
use App\Models\Casualty;
use App\Models\CasualtyCause;
use App\Models\FamilyMember;
use App\Models\Person;
use App\Models\User;
use App\Services\Person\PersonService;
use App\Support\Enums\SpatieRole;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * Add a family member to an affectation.
     *
     * Finds or creates the person by document, then creates the family
     * member record. Optionally uploads evidence files for the person.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $evidenceFiles
     */
    public function addFamilyMember(
        Affectation $affectation,
        array $data,
        array $evidenceFiles = [],
    ): FamilyMember {
        return DB::transaction(function () use ($affectation, $data, $evidenceFiles): FamilyMember {
            $person = $this->findOrCreatePerson($data);

            $familyGroup = $data['family_group'] ?? ($affectation->familyMembers->max('family_group') ?? 0) + 1;

            $familyMember = $affectation->familyMembers()->create([
                'person_id' => $person->id,
                'family_group' => $familyGroup,
                'is_householder' => $data['is_householder'] ?? false,
            ]);

            foreach ($evidenceFiles as $file) {
                $path = $file->store('evidence/family-members/'.$person->id, 's3');

                $person->attachments()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $familyMember->load('person');
        });
    }

    /**
     * Update a family member's data and its associated person.
     *
     * Handles updating the family member record (is_householder, family_group)
     * and the person's personal data. If the document changes, finds or creates
     * a new person and re-links the family member.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $evidenceFiles
     */
    public function updateFamilyMember(
        Affectation $affectation,
        FamilyMember $familyMember,
        array $data,
        array $evidenceFiles = [],
    ): FamilyMember {
        return DB::transaction(function () use ($familyMember, $data, $evidenceFiles): FamilyMember {
            // Update family member fields
            $memberPayload = [];
            if (array_key_exists('is_householder', $data)) {
                $memberPayload['is_householder'] = $data['is_householder'];
            }
            if (array_key_exists('family_group', $data)) {
                $memberPayload['family_group'] = $data['family_group'];
            }
            if ($memberPayload !== []) {
                $familyMember->update($memberPayload);
            }

            // Update person data if provided
            $personDataChanged = isset($data['document_type'])
                || isset($data['document_number'])
                || isset($data['first_name'])
                || isset($data['last_name'])
                || array_key_exists('birth_date', $data);

            if ($personDataChanged && $familyMember->person !== null) {
                $newDocType = $data['document_type'] ?? $familyMember->person->document_type?->value;
                $newDocNumber = isset($data['document_number'])
                    ? Str::upper($data['document_number'])
                    : $familyMember->person->document_number;

                // Check if document changed — may need to link to a different person
                $currentDocType = $familyMember->person->document_type?->value;
                $currentDocNumber = $familyMember->person->document_number;

                if ($newDocType !== $currentDocType || $newDocNumber !== $currentDocNumber) {
                    // Document changed — find or create person
                    $person = $this->findOrCreatePerson([
                        'document_type' => $newDocType,
                        'document_number' => $newDocNumber,
                        'first_name' => $data['first_name'] ?? $familyMember->person->first_name,
                        'last_name' => $data['last_name'] ?? $familyMember->person->last_name,
                        'birth_date' => $data['birth_date'] ?? $familyMember->person->birth_date,
                    ]);
                    $familyMember->update(['person_id' => $person->id]);
                } else {
                    // Same document — update existing person in place
                    $personPayload = [];
                    if (isset($data['first_name'])) {
                        $personPayload['first_name'] = $data['first_name'];
                    }
                    if (isset($data['last_name'])) {
                        $personPayload['last_name'] = $data['last_name'];
                    }
                    if (array_key_exists('birth_date', $data)) {
                        $personPayload['birth_date'] = $data['birth_date'];
                    }
                    if ($personPayload !== []) {
                        $familyMember->person->update($personPayload);
                    }
                }
            }

            // Upload evidence files
            if ($familyMember->person !== null) {
                foreach ($evidenceFiles as $file) {
                    $path = $file->store('evidence/family-members/'.$familyMember->person->id, 's3');

                    $familyMember->person->attachments()->create([
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            return $familyMember->load('person');
        });
    }

    /**
     * Permanently delete a family member's person from the database.
     *
     * Removes the person's evidence files from S3 and deletes the person
     * record. The family member links are removed via cascade. Guards
     * against deleting the main censused person of any affectation.
     *
     * @throws HttpException When the family member does not belong to the
     *                       affectation or the person is a main censused person.
     */
    public function destroyFamilyMemberPerson(Affectation $affectation, FamilyMember $familyMember): void
    {
        abort_if(
            $familyMember->affectation_id !== $affectation->id,
            404,
        );

        $person = $familyMember->person;

        abort_if(
            $person === null || $person->affectation()->exists(),
            422,
            'No se puede eliminar la persona principal del censo.',
        );

        DB::transaction(function () use ($person): void {
            foreach ($person->attachments as $attachment) {
                Storage::disk('s3')->delete($attachment->file_path);
            }

            $person->delete();
        });
    }

    /**
     * Find an existing person by document or create a new one.
     *
     * @param  array<string, mixed>  $data
     */
    private function findOrCreatePerson(array $data): Person
    {
        $existing = Person::query()
            ->where('document_type', $data['document_type'])
            ->where('document_number', Str::upper($data['document_number']))
            ->first();

        if ($existing !== null) {
            if ($existing->birth_date === null && ! empty($data['birth_date'])) {
                $existing->update(['birth_date' => $data['birth_date']]);
                $existing->refresh();
            }

            return $existing;
        }

        $person = Person::query()->create([
            'document_type' => $data['document_type'],
            'document_number' => Str::upper($data['document_number']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'source' => 'family',
            'data_consent' => true,
        ]);

        $person->refresh();

        return $person;
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

    /**
     * Register a casualty (deceased or injured) linked to an affectation.
     *
     * @param  array<string, mixed>  $data
     */
    public function storeCasualty(Affectation $affectation, array $data): Casualty
    {
        $type = CasualtyType::from($data['type']);

        $cause = null;
        if ($type === CasualtyType::Deceased) {
            $cause = CasualtyCause::query()->whereKey($data['cause_id'])->active()->firstOrFail();
        }

        return Casualty::query()->create([
            'affectation_id' => $affectation->id,
            'person_id' => $data['person_id'] ?? null,
            'type' => $type,
            'cause_id' => $cause?->id,
        ]);
    }
}
