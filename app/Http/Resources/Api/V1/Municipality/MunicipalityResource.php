<?php

namespace App\Http\Resources\Api\V1\Municipality;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class MunicipalityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => Str::title(mb_strtolower($this->name)),
            'normalized_name' => $this->normalized_name,
            'department' => Str::title(mb_strtolower($this->department->name)),
            'dpto_code' => $this->department->code,
            'centroid' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
        ];
    }
}
