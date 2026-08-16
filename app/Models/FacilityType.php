<?php

namespace App\Models;

use Database\Factories\FacilityTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityType extends Model
{
    /** @use HasFactory<FacilityTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'display_name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class, 'facility_type_id');
    }
}
