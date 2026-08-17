<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffectationStatus extends Model
{
    protected $fillable = [
        'name',
        'code',
        'icon',
        'text_color',
        'bg_color',
    ];

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'status_id');
    }
}
