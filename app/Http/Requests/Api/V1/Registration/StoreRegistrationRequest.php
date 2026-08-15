<?php

namespace App\Http\Requests\Api\V1\Registration;

use App\Enums\DocumentType;
use App\Enums\SpecialNeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
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
             * Document type of the person.
             *
             * @example CC
             */
            'document_type' => ['required', Rule::in(DocumentType::values())],

            /**
             * Document number of the person.
             *
             * @example 123456789
             */
            'document_number' => ['required', 'string', 'max:32', 'alpha_num'],

            /**
             * First name of the affected person.
             *
             * @example Maria Fernanda
             */
            'first_name' => ['required', 'string', 'max:120'],

            /**
             * Last name of the affected person.
             *
             * @example Garcia Lopez
             */
            'last_name' => ['required', 'string', 'max:120'],

            /**
             * Contact phone number (optional country code +57).
             *
             * @example 573001234567
             */
            'phone' => ['required', 'string', 'max:24', 'regex:/^(\+?57)?3[0-9]{9}$/'],

            /**
             * Municipality where the person was located.
             *
             * @example Buenaventura
             */
            'municipality' => ['required', 'string', 'max:120'],

            /**
             * Neighborhood or sector of the location.
             *
             * @example La Playita
             */
            'neighborhood' => ['nullable', 'string', 'max:120'],

            /**
             * Latitude of the person location.
             *
             * @example 3.8775248
             */
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

            /**
             * Longitude of the person location.
             *
             * @example -77.0206341
             */
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            /**
             * Special needs of the person.
             *
             * @example ["children","disability"]
             */
            'special_needs' => ['nullable', 'array'],
            'special_needs.*' => ['nullable', Rule::in(SpecialNeed::values())],

            /**
             * Consent for personal data processing (Colombian habeas data law).
             *
             * @example true
             */
            'data_consent' => ['required', 'accepted'],

            /**
             * Anti-spam honeypot field; must remain empty.
             *
             * @example
             */
            'website' => ['sometimes', 'max:0'],
        ];
    }
}
