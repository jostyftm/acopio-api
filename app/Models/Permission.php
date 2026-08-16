<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'module_id',
        'display_name',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
