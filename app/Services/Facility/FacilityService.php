<?php

namespace App\Services\Facility;

use App\Models\Facility;
use App\Models\User;
use App\Support\Enums\SpatieRole;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\UploadedFile;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacilityService
{
    /**
     * Build the query for listing facilities with filters and pagination.
     *
     * Admin users see all facilities; non-admin users see only those
     * belonging to their organization. Results include organization, type,
     * and municipality relationships.
     *
     * @param  array{name?: string, status?: string, organization_id?: int, facility_type_id?: int, municipality_id?: int}  $filters
     */
    public function index(User $user, array $filters, int $perPage = 15): CursorPaginator
    {
        $query = QueryBuilder::for(Facility::class)
            ->with(['organization', 'type', 'municipality'])
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('organization_id'),
                AllowedFilter::exact('facility_type_id'),
                AllowedFilter::exact('municipality_id'),
            )
            ->defaultSort('name');

        if (! $user->hasRole(SpatieRole::Admin->value, 'api')) {
            $query->where('organization_id', $user->organization_id);
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * Eager-load all relationships needed for the facility detail view.
     */
    public function show(Facility $facility): Facility
    {
        return $facility->load(['organization', 'type', 'municipality']);
    }

    /**
     * Create a new facility and return it with its relationships.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Facility
    {
        return Facility::query()->create($data)->load(['organization', 'type', 'municipality']);
    }

    /**
     * Update an existing facility and return it with its relationships.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Facility $facility, array $data): Facility
    {
        $facility->update($data);

        return $facility->fresh(['organization', 'type', 'municipality']);
    }

    /**
     * Upload photo files for a facility, creating an attachment record for each.
     *
     * Files are stored on S3 under the facility's evidence directory.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{id: int, url: string, original_name: string, mime: string}>
     */
    public function uploadPhotos(Facility $facility, array $files): array
    {
        $photos = [];

        foreach ($files as $file) {
            $path = $file->store('evidence/facilities/'.$facility->id, 's3');

            $attachment = $facility->photos()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $photos[] = [
                'id' => $attachment->id,
                'url' => $attachment->url,
                'original_name' => $attachment->original_name,
                'mime' => $attachment->mime,
            ];
        }

        return $photos;
    }
}
