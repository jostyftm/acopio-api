<?php

namespace App\Http\Resources\Api\V1\OrganizationType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationTypeResource extends JsonResource
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
            'is_active' => $this->is_active,
        ];
    }
}
