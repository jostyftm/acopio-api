<?php

namespace App\Http\Resources\Api\V1\Module;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
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
            'type' => 'module',
            'attributes' => [
                'parent_id' => $this->parent_id,
                'key' => $this->key,
                'name' => $this->name,
                'order' => $this->order,
                'path' => $this->path,
                'icon' => $this->icon,
                'display_sidebar' => $this->display_sidebar,
                'is_active' => $this->is_active,
                'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
                'children' => ModuleResource::collection($this->whenLoaded('children')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [],
        ];
    }
}
