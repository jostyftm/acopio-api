<?php

namespace App\Http\Resources\Api\V1\CoverageZone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoverageZoneResource extends JsonResource
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
            'type' => 'coverage-zone',
            'attributes' => [
                'name' => $this->name,
                'municipality_id' => $this->municipality_id,
                'municipality' => $this->whenLoaded('municipality', fn () => $this->municipality === null
                    ? null
                    : [
                        'id' => $this->municipality->id,
                        'name' => $this->municipality->name,
                    ]),
                'polygon' => $this->whenNotNull($this->polygonAsGeoJson()),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'organizations' => $this->whenLoaded('organizations', fn () => $this->organizations->map(fn ($organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ])),
            ],
        ];
    }

    /**
     * Converts the stored polygon to a GeoJSON Polygon (longitude, latitude pairs).
     *
     * @return array<string, mixed>|null
     */
    private function polygonAsGeoJson(): ?array
    {
        $polygon = $this->polygon;

        if ($polygon === null) {
            return null;
        }

        $rings = [];

        foreach ($polygon->getLineStrings() as $lineString) {
            $ring = [];

            foreach ($lineString->getPoints() as $point) {
                $ring[] = [$point->getX(), $point->getY()];
            }

            $rings[] = $ring;
        }

        return [
            'type' => 'Polygon',
            'coordinates' => $rings,
        ];
    }
}
