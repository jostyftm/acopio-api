<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\PersonStatus;
use App\Enums\RegistrationSource;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'phone',
        'municipality',
        'neighborhood',
        'latitude',
        'longitude',
        'status',
        'special_needs',
        'source',
        'data_consent',
        'verified_by',
        'verified_at',
        'located_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'status' => PersonStatus::class,
            'source' => RegistrationSource::class,
            'special_needs' => 'array',
            'data_consent' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'verified_at' => 'datetime',
            'located_at' => 'datetime',
        ];
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function searchReports(): HasMany
    {
        return $this->hasMany(SearchReport::class);
    }

    public function smsRecords(): HasMany
    {
        return $this->hasMany(SmsRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $tokens = preg_split('/\s+/', Str::upper($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($tokens, $term): void {
            $q->where('document_number', 'ilike', "%{$term}%")
                ->orWhere('phone', 'ilike', "%{$term}%");

            foreach ($tokens as $token) {
                $q->orWhere('first_name', 'ilike', "%{$token}%")
                    ->orWhere('last_name', 'ilike', "%{$token}%");
            }
        });
    }

    public function scopeInMunicipality(Builder $query, string $municipality): Builder
    {
        return $query->where('municipality', $municipality);
    }

    public function scopeWithStatus(Builder $query, PersonStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeWithLocation(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}
