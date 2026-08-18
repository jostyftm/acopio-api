<?php

namespace App\Http\Resources\Api\V1\AffectationSeverity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationSeverityResource extends JsonResource
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
            'type' => 'affectation-severity',
            'attributes' => [
                'incident_type_id' => $this->incident_type_id,
                'code' => $this->code,
                'display_name' => $this->display_name,
                'description' => $this->description,
                'order' => $this->order,
                'is_active' => $this->is_active,
            ],
            'relationships' => [],
        ];
    }
}
