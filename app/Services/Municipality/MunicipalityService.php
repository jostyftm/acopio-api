<?php

namespace App\Services\Municipality;

use App\Models\Department;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Collection;

class MunicipalityService
{
    /**
     * List municipalities with optional filters for text search and department.
     *
     * Results are ordered by department code then name, and limited to prevent
     * excessive payloads.
     *
     * @param  array{term?: string, department?: string, limit?: int}  $filters
     * @return Collection<int, Municipality>
     */
    public function index(array $filters): Collection
    {
        $query = Municipality::query()->with('department');

        if (! empty($filters['term'])) {
            $query->where('normalized_name', 'ilike', '%'.$filters['term'].'%');
        }

        if (! empty($filters['department'])) {
            $query->whereHas('department', function ($query) use ($filters): void {
                $query->where('name', 'ilike', $filters['department']);
            });
        }

        return $query
            ->orderBy(Department::select('code')->whereColumn('departments.id', 'municipalities.department_id'))
            ->orderBy('name')
            ->limit($filters['limit'] ?? 500)
            ->get();
    }
}
