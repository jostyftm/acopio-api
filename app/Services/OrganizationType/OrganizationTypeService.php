<?php

namespace App\Services\OrganizationType;

use App\Exceptions\ApiException;
use App\Models\OrganizationType;
use Illuminate\Support\Collection;

class OrganizationTypeService
{
    /**
     * List organization types, optionally including inactive ones.
     *
     * @return Collection<int, OrganizationType>
     */
    public function index(bool $includeInactive = false): Collection
    {
        $query = OrganizationType::query()->orderBy('display_name');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Create a new organization type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OrganizationType
    {
        return OrganizationType::query()->create($data);
    }

    /**
     * Update an existing organization type.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(OrganizationType $type, array $data): OrganizationType
    {
        $type->update($data);

        return $type;
    }

    /**
     * Delete an organization type if it has no associated organizations.
     *
     * @throws ApiException When the type has organizations assigned.
     */
    public function delete(OrganizationType $type): void
    {
        if ($type->organizations()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene organizaciones asignadas.', 409);
        }

        $type->delete();
    }
}
