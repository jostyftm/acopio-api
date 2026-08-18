<?php

namespace App\Services\SearchReport;

use App\Enums\PersonStatus;
use App\Enums\ReportStatus;
use App\Models\Person;
use App\Models\SearchReport;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

class SearchReportService
{
    /**
     * Build the query for listing search reports with filters and pagination.
     *
     * Results include the related person and the handler (user), sorted
     * by creation date (newest first).
     *
     * @param  array{status?: string, municipality?: string, document_number?: string}  $filters
     */
    public function index(array $filters, int $perPage = 15): CursorPaginator
    {
        return QueryBuilder::for(SearchReport::class)
            ->with(['person', 'handledBy'])
            ->allowedFilters('status', 'municipality', 'document_number')
            ->defaultSort('-created_at')
            ->cursorPaginate($perPage);
    }

    /**
     * Eager-load relationships needed for the search report detail view.
     */
    public function show(SearchReport $report): SearchReport
    {
        return $report->load(['person', 'handledBy']);
    }

    /**
     * Create a new search report with pending status.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $handledBy = null): SearchReport
    {
        return SearchReport::query()->create([
            ...$data,
            'status' => ReportStatus::Pending,
            'handled_by' => $handledBy?->id,
        ]);
    }

    /**
     * Update a search report's data, or mark it as found if status is 'found'.
     *
     * When status is 'found', the report is linked to the located person
     * and the person's status is updated to 'located'. All operations are
     * wrapped in a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SearchReport $report, array $data, ?User $handledBy = null): SearchReport
    {
        if (($data['status'] ?? null) === 'found') {
            $person = isset($data['person_id'])
                ? Person::find($data['person_id'])
                : null;

            return $this->markFound($report, $person, $handledBy);
        }

        $report->update($data);

        return $report->fresh();
    }

    /**
     * Mark a search report as found and update the linked person's status.
     *
     * All database operations are wrapped in a transaction.
     */
    public function markFound(SearchReport $report, ?Person $person = null, ?User $handledBy = null): SearchReport
    {
        return DB::transaction(function () use ($report, $person, $handledBy): SearchReport {
            $report->update([
                'status' => ReportStatus::Found,
                'person_id' => $person?->id ?? $report->person_id,
                'handled_by' => $handledBy?->id ?? $report->handled_by,
                'located_at' => now(),
            ]);

            if ($person !== null) {
                $person->update([
                    'status' => PersonStatus::Located,
                    'verified_at' => $person->verified_at ?? now(),
                    'located_at' => now(),
                ]);
            }

            return $report->fresh();
        });
    }

    /**
     * Find a pending search report that matches a document number.
     *
     * Used to detect duplicate reports before creating a new one.
     */
    public function findPendingDuplicate(string $documentNumber): ?SearchReport
    {
        return SearchReport::query()
            ->where('document_number', Str::upper($documentNumber))
            ->pending()
            ->first();
    }
}
