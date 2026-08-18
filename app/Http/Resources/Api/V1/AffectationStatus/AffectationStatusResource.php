<?php

namespace App\Http\Resources\Api\V1\AffectationStatus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationStatusResource extends JsonResource
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
            'type' => 'affectation-status',
            'attributes' => [
                'code' => $this->code,
                'name' => $this->name,
                'icon' => $this->icon,
                'text_color' => $this->text_color,
                'bg_color' => $this->bg_color,
            ],
            'relationships' => [],
        ];
    }
}
