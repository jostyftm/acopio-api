<?php

namespace App\Models;

use Database\Factories\SmsRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsRecord extends Model
{
    /** @use HasFactory<SmsRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'origin_phone',
        'body',
        'direction',
        'status',
        'person_id',
        'error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
