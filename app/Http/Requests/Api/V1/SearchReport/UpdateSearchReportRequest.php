<?php

namespace App\Http\Requests\Api\V1\SearchReport;

use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSearchReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('search_report')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /**
             * Person id linked to the found record.
             *
             * @example 42
             */
            'person_id' => ['sometimes', 'nullable', 'integer', 'exists:people,id'],

            /**
             * New status of the report.
             *
             * @example found
             */
            'status' => ['sometimes', Rule::enum(ReportStatus::class)],

            /**
             * Administrative notes about the report.
             *
             * @example Person was located by a rescue team
             */
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
