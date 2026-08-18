<?php

namespace App\Http\Resources\Api\V1\PropertyType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyTypeResource extends JsonResource
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
            'type' => 'property-type',
            'attributes' => [
                'code' => $this->code,
                'display_name' => $this->display_name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ],
            'relationships' => [],
        ];
    }
}
