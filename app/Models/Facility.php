<?php

namespace App\Models;

use App\Enums\FacilityStatus;
use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'facility_type_id',
        'name',
        'municipality_id',
        'address',
        'latitude',
        'longitude',
        'capacity',
        'available',
        'description',
        'status',
        'contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'status' => FacilityStatus::class,
            'capacity' => 'integer',
            'available' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(FacilityType::class, 'facility_type_id');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
