<?php

namespace App\Services\Facility;

use App\Models\Facility;

class FacilityService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Facility
    {
        return Facility::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Facility $facility, array $data): Facility
    {
        $facility->update($data);

        return $facility->fresh();
    }
}
