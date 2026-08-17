<?php

namespace App\Http\Resources\Api\V1\IncidentType;

use App\Http\Resources\Api\V1\AffectationSeverity\AffectationSeverityResource;
use App\Http\Resources\Api\V1\Need\NeedResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentTypeResource extends JsonResource
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
            'code' => $this->code,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'severity_mode' => $this->severity_mode?->value,
            'is_active' => $this->is_active,
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
        ];
    }
}
