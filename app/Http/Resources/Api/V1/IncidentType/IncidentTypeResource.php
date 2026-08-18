<?php

namespace App\Http\Resources\Api\V1\IncidentType;

use App\Http\Resources\Api\V1\AffectationSeverity\AffectationSeverityResource;
use App\Http\Resources\Api\V1\Need\NeedResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentTypeResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API compliant array.
     *
     * Severities and needs are included as relationships since they are
     * loaded via Eloquent whenLoaded and represent separate entities.
     *
     * @return array{id: int, type: string, attributes: array<string, mixed>, relationships: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'incident-type',
            'attributes' => [
                'code' => $this->code,
                'display_name' => $this->display_name,
                'description' => $this->description,
                'severity_mode' => $this->severity_mode?->value,
                'is_active' => $this->is_active,
            ],
            'relationships' => [
                'severities' => $this->whenLoaded(
                    'severities',
                    fn () => AffectationSeverityResource::collection($this->severities),
                    [],
                ),
                'needs' => $this->whenLoaded(
                    'needs',
                    fn () => NeedResource::collection($this->needs),
                    [],
                ),
            ],
        ];
    }
}
