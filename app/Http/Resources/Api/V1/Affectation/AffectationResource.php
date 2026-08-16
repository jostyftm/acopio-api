<?php

namespace App\Http\Resources\Api\V1\Affectation;

use App\Http\Resources\Api\V1\Need\NeedResource;
use App\Models\Attachment;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AffectationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'affectation',
            'attributes' => [
                'person_id' => $this->person_id,
                'reported_by' => $this->reported_by,
                'organization_id' => $this->organization_id,
                'severity' => $this->severity?->value,
                'description' => $this->description,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'needs' => $this->whenLoaded(
                    'needs',
                    fn () => NeedResource::collection($this->needs),
                    [],
                ),
                'evidence' => $this->whenLoaded('attachments', fn () => $this->attachments->map(
                    fn (Attachment $attachment): array => $this->attachmentPayload($attachment),
                )->values(), []),
                'family_members' => $this->whenLoaded('familyMembers', fn () => $this->familyMembers->map(
                    fn (FamilyMember $member): array => [
                        'id' => $member->id,
                        'is_householder' => $member->is_householder,
                        'person' => $member->person === null ? null : [
                            'id' => $member->person->id,
                            'document_type' => $member->person->document_type?->value,
                            'document_number' => $member->person->document_number,
                            'full_name' => $member->person->full_name,
                            'birth_date' => $member->person->birth_date?->format('Y-m-d'),
                            'current_age' => $member->person->current_age,
                            'evidence' => $member->person->attachments->map(
                                fn (Attachment $attachment): array => $this->attachmentPayload($attachment),
                            )->values(),
                        ],
                    ],
                )->values(), []),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'person' => $this->whenLoaded('person', fn (): ?array => $this->person === null
                    ? null
                    : [
                        'id' => $this->person->id,
                        'document_type' => $this->person->document_type?->value,
                        'document_number' => $this->person->document_number,
                        'full_name' => $this->person->full_name,
                        'birth_date' => $this->person->birth_date?->format('Y-m-d'),
                        'current_age' => $this->person->current_age,
                        'phone' => $this->person->phone,
                        'municipality' => $this->municipalityName(),
                        'neighborhood' => $this->person->neighborhood,
                        'address' => $this->person->address,
                        'sector' => $this->person->sector?->value,
                    ]),
                'reporter' => $this->whenLoaded('reporter', fn (): ?array => $this->reporter === null
                    ? null
                    : [
                        'id' => $this->reporter->id,
                        'name' => $this->reporter->name,
                        'email' => $this->reporter->email,
                    ]),
                'organization' => $this->whenLoaded('organization', fn (): ?array => $this->organization === null
                    ? null
                    : [
                        'id' => $this->organization->id,
                        'name' => $this->organization->name,
                    ]),
            ],
        ];
    }

    /**
     * @return array{id: int, url: string, original_name: string, mime: string}
     */
    private function attachmentPayload(Attachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'url' => $attachment->url,
            'original_name' => $attachment->original_name,
            'mime' => $attachment->mime,
        ];
    }

    private function municipalityName(): ?string
    {
        if ($this->person === null || $this->person->municipality === null) {
            return null;
        }

        return Str::title(mb_strtolower($this->person->municipality->name));
    }
}
