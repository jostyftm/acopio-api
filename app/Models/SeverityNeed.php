<?php

namespace App\Models;

use App\Enums\ImpactLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeverityNeed extends Model
{
    protected $fillable = [
        'code_level',
        'display_name',
    ];

    protected function casts(): array
    {
        return [
            'code_level' => ImpactLevel::class,
        ];
    }

    public function needs(): HasMany
    {
        return $this->hasMany(Need::class);
    }
}
