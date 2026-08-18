<?php

namespace App\Http\Resources\Api\V1\Need;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NeedResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API compliant array.
     *
     * The severity relationship is conditionally included when the
     * severityNeed relation is loaded via with('severityNeed').
     *
     * @return array{id: int, type: string, attributes: array<string, mixed>, relationships: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'need',
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
            ],
            'relationships' => [
                'severity' => $this->severityNeed === null ? null : [
                    'id' => $this->severityNeed->id,
                    'code_level' => $this->severityNeed->code_level?->value,
                    'display_name' => $this->severityNeed->display_name,
                ],
            ],
        ];
    }
}
