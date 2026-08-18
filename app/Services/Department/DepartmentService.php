<?php

namespace App\Services\Department;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    /**
     * List departments from the DIVIPOLA catalog with an optional text filter.
     *
     * @param  array{term?: string}  $filters
     * @return Collection<int, Department>
     */
    public function index(array $filters = []): Collection
    {
        return Department::query()
            ->when($filters['term'] ?? null, function ($query, string $term): void {
                $query->where('normalized_name', 'ilike', '%'.$term.'%');
            })
            ->orderBy('name')
            ->get();
    }
}
