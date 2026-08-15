<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AffectationEvidence extends Model
{
    protected $fillable = [
        'affectation_id',
        'file_path',
        'original_name',
        'mime',
        'size',
    ];

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(Affectation::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('s3')->url($this->file_path);
    }
}
