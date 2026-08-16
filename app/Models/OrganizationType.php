<?php

namespace App\Models;

use Database\Factories\OrganizationTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationType extends Model
{
    /** @use HasFactory<OrganizationTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'display_name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'organization_type_id');
    }
}
