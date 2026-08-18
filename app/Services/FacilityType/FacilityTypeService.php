<?php

namespace App\Services\FacilityType;

use App\Exceptions\ApiException;
use App\Models\FacilityType;
use Illuminate\Support\Collection;

class FacilityTypeService
{
    /**
     * List facility types, optionally including inactive ones.
     *
     * @return Collection<int, FacilityType>
     */
    public function index(bool $includeInactive = false): Collection
    {
        $query = FacilityType::query()->orderBy('display_name');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Create a new facility type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FacilityType
    {
        return FacilityType::query()->create($data);
    }

    /**
     * Update an existing facility type.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FacilityType $type, array $data): FacilityType
    {
        $type->update($data);

        return $type;
    }

    /**
     * Delete a facility type if it has no associated facilities.
     *
     * @throws ApiException When the type has facilities assigned.
     */
    public function delete(FacilityType $type): void
    {
        if ($type->facilities()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene espacios asignados.', 409);
        }

        $type->delete();
    }
}
