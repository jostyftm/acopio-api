<?php

namespace App\Http\Resources\Api\V1\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'type' => 'role',
            'attributes' => [
                'name' => $this->name,
                'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions
                    ->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])
                    ->values()),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [],
        ];
    }
}
