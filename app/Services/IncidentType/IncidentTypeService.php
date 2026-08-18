<?php

namespace App\Services\IncidentType;

use App\Exceptions\ApiException;
use App\Models\IncidentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncidentTypeService
{
    /**
     * List incident types with their severities and needs, optionally including inactive ones.
     *
     * @return Collection<int, IncidentType>
     */
    public function index(bool $includeInactive = false): Collection
    {
        $query = IncidentType::query()->with('severities', 'needs')->orderBy('display_name');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Create a new incident type and sync its needs relationship.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): IncidentType
    {
        return DB::transaction(function () use ($data): IncidentType {
            $type = IncidentType::query()->create(collect($data)->except('needs')->toArray());

            if (isset($data['needs'])) {
                $type->needs()->sync($data['needs']);
            }

            return $type->load('severities', 'needs');
        });
    }

    /**
     * Eager-load severities and needs for an incident type.
     */
    public function loadRelations(IncidentType $type): IncidentType
    {
        return $type->load('severities', 'needs');
    }

    /**
     * Update an incident type and sync its needs relationship.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(IncidentType $type, array $data): IncidentType
    {
        return DB::transaction(function () use ($type, $data): IncidentType {
            $type->update(collect($data)->except('needs')->toArray());

            if (isset($data['needs'])) {
                $type->needs()->sync($data['needs']);
            }

            return $type->load('severities', 'needs');
        });
    }

    /**
     * Delete an incident type if it has no associated affectations.
     *
     * @throws ApiException When the type has affectations assigned.
     */
    public function delete(IncidentType $type): void
    {
        if ($type->affectations()->exists()) {
            throw new ApiException('No se puede eliminar un tipo que tiene afectaciones asignadas.', 409);
        }

        $type->delete();
    }
}
