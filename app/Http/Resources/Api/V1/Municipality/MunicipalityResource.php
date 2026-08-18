<?php

namespace App\Http\Resources\Api\V1\Municipality;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class MunicipalityResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API compliant array.
     *
     * The centroid is a computed attribute derived from the model's latitude/longitude
     * columns, not a separate Eloquent relationship.
     *
     * @return array{id: int, type: string, attributes: array<string, mixed>, relationships: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'municipality',
            'attributes' => [
                'code' => $this->code,
                'name' => Str::title(mb_strtolower($this->name)),
                'normalized_name' => $this->normalized_name,
                'department' => Str::title(mb_strtolower($this->department->name)),
                'dpto_code' => $this->department->code,
                'centroid' => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                ],
            ],
            'relationships' => [],
        ];
    }
}
