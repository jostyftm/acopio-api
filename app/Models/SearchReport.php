<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\ReportStatus;
use Database\Factories\SearchReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchReport extends Model
{
    /** @use HasFactory<SearchReportFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id',
        'searched_name',
        'document_type',
        'document_number',
        'municipality',
        'reporter_name',
        'reporter_phone',
        'relationship',
        'status',
        'notes',
        'handled_by',
        'located_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'status' => ReportStatus::class,
            'located_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Pending->value);
    }
}
