<?php

namespace App\Models;

use Database\Factories\AffectationSeverityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AffectationSeverity extends Model
{
    /** @use HasFactory<AffectationSeverityFactory> */
    use HasFactory;

    protected $fillable = [
        'incident_type_id',
        'code',
        'display_name',
        'description',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function incidentType(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class);
    }

    public function affectations(): BelongsToMany
    {
        return $this->belongsToMany(Affectation::class, 'affectation_severity')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
