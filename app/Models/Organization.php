<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_type_id',
        'municipality_id',
        'name',
        'nit',
        'description',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class, 'organization_type_id');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    public function coverageZones(): BelongsToMany
    {
        return $this->belongsToMany(CoverageZone::class, 'organization_coverage_zone')->withTimestamps();
    }
}
