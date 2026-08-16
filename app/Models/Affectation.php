<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Affectation extends Model
{
    protected $fillable = [
        'person_id',
        'incident_type_id',
        'reported_by',
        'organization_id',
        'description',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'location' => Point::class,
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
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
