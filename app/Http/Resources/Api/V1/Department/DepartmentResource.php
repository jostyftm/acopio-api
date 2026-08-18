<?php

namespace App\Http\Resources\Api\V1\Department;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API compliant array.
     *
     * Departments use the DIVIPOLA code as identifier since they have no numeric primary key.
     *
     * @return array{id: string, type: string, attributes: array<string, mixed>, relationships: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->code,
            'type' => 'department',
            'attributes' => [
                'code' => $this->code,
                'name' => Str::title(mb_strtolower($this->name)),
                'normalized_name' => $this->normalized_name,
                'centroid' => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                ],
            ],
            'relationships' => [],
        ];
    }
}
