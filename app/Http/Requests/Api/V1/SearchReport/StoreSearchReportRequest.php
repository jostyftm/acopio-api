<?php

namespace App\Http\Requests\Api\V1\SearchReport;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSearchReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
             * Person id when the report is linked to an existing record.
             *
             * @example 42
             */
            'person_id' => ['nullable', 'integer', 'exists:people,id'],

            /**
             * Name of the missing person being searched for.
             *
             * @example Juan Perez
             */
            'searched_name' => ['required', 'string', 'max:240'],

            /**
             * Document type of the missing person.
             *
             * @example CC
             */
            'document_type' => ['nullable', Rule::in(DocumentType::values())],

            /**
             * Document number of the missing person.
             *
             * @example 987654321
             */
            'document_number' => ['nullable', 'string', 'max:32', 'alpha_num'],

            /**
             * Municipality where the person went missing.
             *
             * @example Buenaventura
             */
            'municipality' => ['nullable', 'string', 'max:120'],

            /**
             * Full name of the person filing the report.
             *
             * @example Luisa Martinez
             */
            'reporter_name' => ['required', 'string', 'max:240'],

            /**
             * Contact phone of the reporter (optional country code +57).
             *
             * @example 573001234567
             */
            'reporter_phone' => ['required', 'string', 'max:24', 'regex:/^(\+?57)?3[0-9]{9}$/'],

            /**
             * Relationship between the reporter and the missing person.
             *
             * @example Mother
             */
            'relationship' => ['nullable', 'string', 'max:120'],

            /**
             * Additional notes about the report.
             *
             * @example Last seen near the port
             */
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
