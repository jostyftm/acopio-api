<?php

namespace App\Http\Resources\Api\V1\Affectation;

use App\Http\Resources\Api\V1\Need\NeedResource;
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
                'evidence' => $this->whenLoaded('evidence', fn () => $this->evidence->map(
                    fn ($file): array => [
                        'id' => $file->id,
                        'url' => $file->url,
                        'original_name' => $file->original_name,
                        'mime' => $file->mime,
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

    private function municipalityName(): ?string
    {
        if ($this->person === null || $this->person->municipality === null) {
            return null;
        }

        return Str::title(mb_strtolower($this->person->municipality->name));
    }
}
