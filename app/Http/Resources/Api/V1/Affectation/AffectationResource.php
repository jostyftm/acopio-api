<?php

namespace App\Http\Resources\Api\V1\Affectation;

use App\Http\Resources\Api\V1\AffectationSeverity\AffectationSeverityResource;
use App\Http\Resources\Api\V1\AffectationStatus\AffectationStatusResource;
use App\Http\Resources\Api\V1\Attachment\AttachmentResource;
use App\Http\Resources\Api\V1\FamilyMember\FamilyMemberResource;
use App\Http\Resources\Api\V1\IncidentType\IncidentTypeResource;
use App\Http\Resources\Api\V1\Need\NeedResource;
use App\Http\Resources\Api\V1\Organization\OrganizationResource;
use App\Http\Resources\Api\V1\Person\PersonResource;
use App\Http\Resources\Api\V1\PropertyType\PropertyTypeResource;
use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API compliant array.
     *
     * @return array{id: int, type: string, attributes: array<string, mixed>, relationships: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'affectation',
            'attributes' => $this->getAttributes(),
            'relationships' => $this->getRelationships(),
        ];
    }

    /**
     * Scalar attributes for the affectation resource.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return [
            'person_id' => $this->person_id,
            'incident_type_id' => $this->incident_type_id,
            'reported_by' => $this->reported_by,
            'organization_id' => $this->organization_id,
            'status_id' => $this->status_id,
            'address' => $this->address,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'verified_at' => $this->verified_at?->toISOString(),
            'located_at' => $this->located_at?->toISOString(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Related resources for the affectation.
     *
     * @return array<string, mixed>
     */
    public function getRelationships(): array
    {
        return [
            'status' => $this->whenLoaded('status', fn () => AffectationStatusResource::make($this->status)),
            'incident_type' => $this->whenLoaded('incidentType', fn () => IncidentTypeResource::make($this->incidentType)),
            'severities' => $this->whenLoaded('severities', fn () => AffectationSeverityResource::collection($this->severities)),
            'property_types' => $this->whenLoaded('propertyTypes', fn () => PropertyTypeResource::collection($this->propertyTypes)),
            'needs' => $this->whenLoaded('needs', fn () => NeedResource::collection($this->needs)),
            'evidence' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
            'family_members' => $this->whenLoaded('familyMembers', fn () => FamilyMemberResource::collection($this->familyMembers)),
            'person' => $this->whenLoaded('person', fn () => PersonResource::make($this->person)),
            'reporter' => $this->whenLoaded('reporter', fn () => UserResource::make($this->reporter)),
            'verified_by' => $this->whenLoaded('verifiedBy', fn () => UserResource::make($this->verifiedBy)),
            'organization' => $this->whenLoaded('organization', fn () => OrganizationResource::make($this->organization)),
        ];
    }
}
