<?php

namespace App\Http\Resources\Api\V1\Facility;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityResource extends JsonResource
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
            'type' => 'facility',
            'attributes' => [
                'name' => $this->name,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'capacity' => $this->capacity,
                'available' => $this->available,
                'description' => $this->description,
                'status' => $this->status->value,
                'organization' => $this->whenLoaded('organization', fn () => [
                    'id' => $this->organization->id,
                    'name' => $this->organization->name,
                ]),
                'facility_type' => $this->whenLoaded('type', fn () => [
                    'id' => $this->type->id,
                    'code' => $this->type->code,
                    'display_name' => $this->type->display_name,
                ]),
                'municipality' => $this->whenLoaded('municipality', fn () => $this->municipality === null ? null : [
                    'id' => $this->municipality->id,
                    'code' => $this->municipality->code,
                    'name' => $this->municipality->name,
                ]),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [],
        ];
    }
}
