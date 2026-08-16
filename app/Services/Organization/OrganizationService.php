<?php

namespace App\Services\Organization;

use App\Models\Organization;

class OrganizationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        return Organization::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization->fresh();
    }
}
