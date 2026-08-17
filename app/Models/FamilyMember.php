<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'affectation_id',
        'person_id',
        'is_householder',
        'family_group',
    ];

    protected function casts(): array
    {
        return [
            'is_householder' => 'boolean',
            'family_group' => 'integer',
        ];
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(Affectation::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
