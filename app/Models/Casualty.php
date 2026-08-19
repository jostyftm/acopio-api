<?php

namespace App\Models;

use App\Enums\CasualtyType;
use Database\Factories\CasualtyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Casualty extends Model
{
    /** @use HasFactory<CasualtyFactory> */
    use HasFactory;

    protected $fillable = [
        'affectation_id',
        'person_id',
        'type',
        'cause_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => CasualtyType::class,
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

    public function cause(): BelongsTo
    {
        return $this->belongsTo(CasualtyCause::class);
    }
}
