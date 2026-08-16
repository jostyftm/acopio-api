<?php

namespace App\Http\Resources\Api\V1\Organization;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'type' => 'organization',
            'attributes' => [
                'name' => $this->name,
                'nit' => $this->nit,
                'description' => $this->description,
                'contact_name' => $this->contact_name,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'status' => $this->status->value,
                'organization_type' => $this->whenLoaded('type', fn () => [
                    'id' => $this->type->id,
                    'code' => $this->type->code,
                    'display_name' => $this->type->display_name,
                ]),
                'user_count' => $this->whenCounted('users'),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [],
        ];
    }
}
