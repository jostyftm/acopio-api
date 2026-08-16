<?php

namespace App\Http\Resources\Api\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'type' => 'user',
            'attributes' => $this->getAttributes(),
            'relationships' => $this->getRelationships(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'provider' => $this->provider,
            'roles' => $this->getRoleNames(),
            'is_active' => $this->is_active,
            'organization' => $this->whenLoaded('organization', fn () => $this->organization === null ? null : [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRelationships(): array
    {
        return [];
    }
}
