<?php

namespace App\Http\Requests\Api\V1\Person;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchPersonRequest extends FormRequest
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
             * Search term by name, last name, document number or phone.
             *
             * @example Maria Garcia
             */
            'term' => ['nullable', 'string', 'max:240'],

            /**
             * Municipality to filter by.
             *
             * @example Buenaventura
             */
            'municipality' => ['nullable', 'string', 'max:120'],

            /**
             * Registration status of the person.
             *
             * @example located
             */
            'status' => ['nullable', 'string', 'max:20'],

            /**
             * Document type of the person.
             *
             * @example CC
             */
            'document_type' => ['nullable', Rule::in(DocumentType::values())],
        ];
    }
}
