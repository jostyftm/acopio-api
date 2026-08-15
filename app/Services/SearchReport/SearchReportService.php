<?php

namespace App\Services\SearchReport;

use App\Enums\PersonStatus;
use App\Enums\ReportStatus;
use App\Models\Person;
use App\Models\SearchReport;
use App\Models\User;
use Illuminate\Support\Str;

class SearchReportService
{
    /**
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

    public function markFound(SearchReport $report, ?Person $person = null, ?User $handledBy = null): SearchReport
    {
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
    }

    public function findPendingDuplicate(string $documentNumber): ?SearchReport
    {
        return SearchReport::query()
            ->where('document_number', Str::upper($documentNumber))
            ->pending()
            ->first();
    }
}
