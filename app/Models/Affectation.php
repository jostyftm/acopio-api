<?php

namespace App\Models;

use App\Enums\AffectationSeverity;
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
        'reported_by',
        'organization_id',
        'severity',
        'description',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'severity' => AffectationSeverity::class,
            'location' => Point::class,
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
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
