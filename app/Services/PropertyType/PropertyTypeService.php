<?php

namespace App\Services\PropertyType;

use App\Exceptions\ApiException;
use App\Models\PropertyType;
use Illuminate\Support\Collection;

class PropertyTypeService
{
    /**
     * List property types, optionally including inactive ones.
     *
     * @return Collection<int, PropertyType>
     */
    public function index(bool $includeInactive = false): Collection
    {
        $query = PropertyType::query()->orderBy('display_name');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Create a new property type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PropertyType
    {
        return PropertyType::query()->create($data);
    }

    /**
     * Update an existing property type.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyType $type, array $data): PropertyType
    {
        $type->update($data);

        return $type;
    }

    /**
     * Delete a property type if it has no associated affectations.
     *
     * @throws ApiException When the type has affectations assigned.
     */
    public function delete(PropertyType $type): void
    {
        if ($type->affectations()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene afectaciones asignadas.', 409);
        }

        $type->delete();
    }
}
