<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Point;
use Database\Factories\AffectationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Affectation extends Model
{
    /** @use HasFactory<AffectationFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id',
        'incident_type_id',
        'reported_by',
        'organization_id',
        'status_id',
        'description',
        'address',
        'location',
        'verified_by',
        'verified_at',
        'located_at',
    ];

    protected function casts(): array
    {
        return [
            'location' => Point::class,
            'verified_at' => 'datetime',
            'located_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AffectationStatus::class, 'status_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function incidentType(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class);
    }

    public function severities(): BelongsToMany
    {
        return $this->belongsToMany(AffectationSeverity::class, 'affectation_severity')->withTimestamps();
    }

    public function propertyTypes(): BelongsToMany
    {
        return $this->belongsToMany(PropertyType::class)->withTimestamps();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function needs(): BelongsToMany
    {
        return $this->belongsToMany(Need::class)->withTimestamps();
    }

    public function getLatitudeAttribute(): ?float
    {
        return $this->location?->getLatitude();
    }

    public function getLongitudeAttribute(): ?float
    {
        return $this->location?->getLongitude();
    }
}
