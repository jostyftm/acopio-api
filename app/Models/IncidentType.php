<?php

namespace App\Models;

use App\Enums\SeverityMode;
use Database\Factories\IncidentTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentType extends Model
{
    /** @use HasFactory<IncidentTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'display_name',
        'description',
        'severity_mode',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'severity_mode' => SeverityMode::class,
            'is_active' => 'boolean',
        ];
    }

    public function severities(): HasMany
    {
        return $this->hasMany(AffectationSeverity::class)->orderBy('order');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function needs(): BelongsToMany
    {
        return $this->belongsToMany(Need::class, 'incident_type_need')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
