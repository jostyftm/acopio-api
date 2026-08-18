<?php

namespace App\Services\AffectationSeverity;

use App\Exceptions\ApiException;
use App\Models\AffectationSeverity;

class AffectationSeverityService
{
    /**
     * Create a new affectation severity.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AffectationSeverity
    {
        return AffectationSeverity::query()->create($data);
    }

    /**
     * Update an existing affectation severity.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AffectationSeverity $severity, array $data): AffectationSeverity
    {
        $severity->update($data);

        return $severity->fresh('incidentType');
    }

    /**
     * Delete an affectation severity if it has no associated affectations.
     *
     * @throws ApiException When the severity has affectations assigned.
     */
    public function delete(AffectationSeverity $severity): void
    {
        if ($severity->affectations()->exists()) {
            throw new ApiException('No se puede eliminar una gravedad que tiene afectaciones asignadas.', 409);
        }

        $severity->delete();
    }
}
