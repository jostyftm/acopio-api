<?php

namespace App\Services\Person;

use App\Enums\PersonStatus;
use App\Models\Municipality;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PersonService
{
    public function index(): QueryBuilder
    {
        return QueryBuilder::for(Person::class)
            ->with(['municipality', 'affectation.needs', 'affectation.evidence'])
            ->allowedFilters(
                ...[
                    AllowedFilter::exact('status'),
                    AllowedFilter::callback(
                        'municipality',
                        fn (Builder $query, string $value): Builder => $query->inMunicipality($value),
                    ),
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
            ->with(['municipality', 'affectation.needs'])
            ->allowedFilters(
                ...[
                    AllowedFilter::exact('status'),
                    AllowedFilter::callback(
                        'municipality',
                        fn (Builder $query, string $value): Builder => $query->inMunicipality($value),
                    ),
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
            ...($location !== null ? $this->locationPayload($location) : []),
        ]);

        return $person->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Person $person, array $data): Person
    {
        $person->update($this->normalizeLocation($data));

        return $person->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeLocation(array $data): array
    {
        $data = $this->resolveMunicipality($data);

        if (! array_key_exists('latitude', $data) && ! array_key_exists('longitude', $data)) {
            return $data;
        }

        $payload = $this->locationPayload($data);
        unset($data['latitude'], $data['longitude']);

        return [...$data, ...$payload];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveMunicipality(array $data): array
    {
        if (! array_key_exists('municipality', $data)) {
            return $data;
        }

        $name = (string) $data['municipality'];
        unset($data['municipality']);

        $municipality = trim($name) === ''
            ? null
            : Municipality::findByNormalizedName($name);

        if ($municipality !== null) {
            $data['municipality_id'] = $municipality->id;
        } elseif (array_key_exists('municipality_id', $data)) {
            unset($data['municipality_id']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    private function locationPayload(array $location): array
    {
        $latitude = isset($location['latitude']) ? (float) $location['latitude'] : null;
        $longitude = isset($location['longitude']) ? (float) $location['longitude'] : null;

        return [
            'location' => Person::locationFromLatLng($latitude, $longitude),
            ...(Municipality::resolveForCoordinates($latitude, $longitude) ?? []),
        ];
    }
}
