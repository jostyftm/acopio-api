<?php

namespace App\Http\Resources\Api\V1\Need;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NeedResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'severity' => $this->severityNeed === null ? null : [
                'id' => $this->severityNeed->id,
                'code_level' => $this->severityNeed->code_level?->value,
                'display_name' => $this->severityNeed->display_name,
            ],
        ];
    }
}
