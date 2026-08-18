<?php

namespace App\Http\Resources\Api\V1\FamilyMember;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyMemberResource extends JsonResource
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
            'type' => 'family-member',
            'attributes' => [
                'is_householder' => $this->is_householder,
            ],
            'relationships' => [
                'person' => $this->whenLoaded('person', fn () => FamilyMemberPersonResource::make($this->person)),
            ],
        ];
    }
}
