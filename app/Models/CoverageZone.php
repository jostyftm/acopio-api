<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Polygon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CoverageZone extends Model
{
    protected $fillable = [
        'municipality_id',
        'name',
        'polygon',
        'map_center',
        'map_zoom',
    ];

    protected function casts(): array
    {
        return [
            'polygon' => Polygon::class,
            'map_center' => 'array',
            'map_zoom' => 'decimal:2',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_coverage_zone')->withTimestamps();
    }

    /**
     * Resolves the coverage zone that covers the given coordinates.
     */
    public static function findForCoordinates(float $latitude, float $longitude): ?self
    {
        return self::query()
            ->whereRaw(
                'ST_Covers(polygon, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
                [$longitude, $latitude],
            )
            ->first();
    }
}
