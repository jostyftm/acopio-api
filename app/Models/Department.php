<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'code',
        'name',
        'normalized_name',
        'centroid',
    ];

    protected function casts(): array
    {
        return [
            'centroid' => Point::class,
        ];
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
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
