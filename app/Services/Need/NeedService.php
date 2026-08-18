<?php

namespace App\Services\Need;

use App\Models\Need;
use Illuminate\Support\Collection;

class NeedService
{
    /**
     * List active needs with their severity level, ordered by name.
     *
     * @return Collection<int, Need>
     */
    public function index(): Collection
    {
        return Need::query()
            ->active()
            ->with('severityNeed')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new need with a normalized name.
     *
     * @param  array{name: string, description?: string, severity_need_id?: int}  $data
     */
    public function create(array $data): Need
    {
        return Need::query()->create([
            'name' => $data['name'],
            'normalized_name' => Need::normalizeName($data['name']),
            'description' => $data['description'] ?? null,
            'severity_need_id' => $data['severity_need_id'] ?? null,
        ]);
    }
}
