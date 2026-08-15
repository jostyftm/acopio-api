<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Need extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
        'description',
        'severity_need_id',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function severityNeed(): BelongsTo
    {
        return $this->belongsTo(SeverityNeed::class);
    }

    public function affectations(): BelongsToMany
    {
        return $this->belongsToMany(Affectation::class)->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public static function normalizeName(string $name): string
    {
        return Str::upper(iconv('UTF-8', 'ASCII//TRANSLIT', trim($name)));
    }
}
