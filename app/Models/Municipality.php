<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\MultiPolygon;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Municipality extends Model
{
    protected $fillable = [
        'department_id',
        'code',
        'name',
        'normalized_name',
        'centroid',
        'boundary',
    ];

    protected function casts(): array
    {
        return [
            'centroid' => Point::class,
            'boundary' => MultiPolygon::class,
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return array{municipality_id: int}|null
     */
    public static function resolveForCoordinates(?float $latitude, ?float $longitude): ?array
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $municipality = self::findByLatLng($latitude, $longitude);

        if ($municipality === null) {
            return null;
        }

        return [
            'municipality_id' => $municipality->id,
        ];
    }

    public static function findByNormalizedName(string $name): ?self
    {
        $normalized = Str::upper(iconv('UTF-8', 'ASCII//TRANSLIT', trim($name)));

        if ($normalized === '') {
            return null;
        }

        return self::query()
            ->where('normalized_name', $normalized)
            ->first();
    }

    public static function findByCode(string $code): ?self
    {
        return self::query()
            ->where('code', trim($code))
            ->first();
    }

    public static function findByLatLng(float $latitude, float $longitude): ?self
    {
        return self::query()
            ->whereRaw(
                'ST_Covers(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
                [$longitude, $latitude],
            )
            ->first();
    }

    public function getLatitudeAttribute(): ?float
    {
        return $this->centroid?->getLatitude();
    }

    public function getLongitudeAttribute(): ?float
    {
        return $this->centroid?->getLongitude();
    }
}
