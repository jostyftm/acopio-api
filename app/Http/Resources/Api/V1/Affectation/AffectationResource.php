<?php

namespace App\Http\Resources\Api\V1\Affectation;

use App\Http\Resources\Api\V1\Need\NeedResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationResource extends JsonResource
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
            'severity' => $this->severity?->value,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'needs' => $this->whenLoaded(
                'needs',
                fn () => NeedResource::collection($this->needs),
                [],
            ),
            'evidence' => $this->whenLoaded('evidence', fn () => $this->evidence->map(
                fn ($file): array => [
                    'id' => $file->id,
                    'url' => $file->url,
                    'original_name' => $file->original_name,
                    'mime' => $file->mime,
                ],
            )->values(), []),
        ];
    }
}
