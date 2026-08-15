<?php

namespace App\Services\Person;

use App\Enums\PersonStatus;
use App\Models\Person;
use App\Models\User;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PersonService
{
    public function index(): QueryBuilder
    {
        return QueryBuilder::for(Person::class)
            ->allowedFilters(
                ...[
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('municipality'),
                    AllowedFilter::exact('document_type'),
                    AllowedFilter::exact('source'),
                    AllowedFilter::exact('document_number'),
                    AllowedFilter::exact('phone'),
                    AllowedFilter::partial('first_name'),
                    AllowedFilter::partial('last_name'),
                    AllowedFilter::exact('special_needs'),
                ]
            )
            ->allowedSorts(
                ...[
                    'created_at',
                    'first_name',
                    'last_name',
                    'status',
                ]
            )
            ->allowedIncludes(
                ...[
                    'verifiedBy',
                    'searchReports',
                ]
            )
            ->defaultSort('-created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function publicSearch(array $filters): QueryBuilder
    {
        $query = QueryBuilder::for(Person::class)
            ->allowedFilters(
                ...[
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('municipality'),
                    AllowedFilter::exact('document_type'),
                ]
            )
            ->allowedSorts(...['created_at', 'first_name'])
            ->defaultSort('-created_at');

        if (($filters['term'] ?? '') !== '') {
            $query->search((string) $filters['term']);
        }

        return $query;
    }

    public function verify(Person $person, User $verifier): Person
    {
        $person->update([
            'status' => PersonStatus::Verified,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        return $person->fresh();
    }

    public function locate(Person $person, ?array $location = null): Person
    {
        $person->update([
            'status' => PersonStatus::Located,
            'verified_at' => $person->verified_at ?? now(),
            'located_at' => now(),
            ...($location !== null
                ? ['latitude' => $location['latitude'], 'longitude' => $location['longitude']]
                : []),
        ]);

        return $person->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Person $person, array $data): Person
    {
        $person->update($data);

        return $person->fresh();
    }
}
