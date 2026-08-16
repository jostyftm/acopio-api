<?php

namespace App\Http\Resources\Api\V1\Module;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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
            'type' => 'permission',
            'attributes' => [
                'module_id' => $this->module_id,
                'module' => $this->whenLoaded('module', fn () => [
                    'id' => $this->module->id,
                    'key' => $this->module->key,
                    'name' => $this->module->name,
                ]),
                'name' => $this->name,
                'action' => $this->action(),
                'guard_name' => $this->guard_name,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [],
        ];
    }

    protected function action(): string
    {
        $separator = strpos($this->name, '.');

        return $separator === false ? $this->name : substr($this->name, $separator + 1);
    }
}
