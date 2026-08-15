<?php

namespace App\Http\Resources\Api\V1\Person;

use App\Http\Resources\Api\V1\SearchReport\SearchReportResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PersonResource extends JsonResource
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
            'type' => 'person',
            'attributes' => $this->getAttributes(),
            'relationships' => $this->getRelationships(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return [
            'document_type' => $this->document_type?->value,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'municipality' => $this->municipalityName(),
            'neighborhood' => $this->neighborhood,
            'address' => $this->address,
            'sector' => $this->sector?->value,
            'municipality_id' => $this->municipality_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status?->value,
            'special_needs' => $this->special_needs ?? [],
            'source' => $this->source?->value,
            'data_consent' => $this->data_consent,
            'verified_at' => $this->verified_at,
            'located_at' => $this->located_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRelationships(): array
    {
        return [
            'verified_by' => $this->whenLoaded('verifiedBy', fn (): ?array => $this->verifiedBy?->only(['id', 'name'])),
            'search_reports' => $this->whenLoaded('searchReports', fn () => SearchReportResource::collection($this->searchReports)),
            'affectation' => $this->whenLoaded('affectation', fn (): ?array => $this->affectation === null
                ? null
                : [
                    'id' => $this->affectation->id,
                    'severity' => $this->affectation->severity?->value,
                    'description' => $this->affectation->description,
                    'latitude' => $this->affectation->latitude,
                    'longitude' => $this->affectation->longitude,
                    'needs' => $this->affectation->needs->map(
                        fn ($need): array => ['id' => $need->id, 'name' => $need->name],
                    )->values(),
                    'evidence' => $this->affectation->evidence->map(
                        fn ($file): array => [
                            'id' => $file->id,
                            'url' => $file->url,
                            'original_name' => $file->original_name,
                            'mime' => $file->mime,
                        ],
                    )->values(),
                ]),
        ];
    }

    public function municipalityName(): ?string
    {
        if ($this->municipality === null) {
            return null;
        }

        return Str::title(mb_strtolower($this->municipality->name));
    }
}
