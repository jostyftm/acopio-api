<?php

namespace App\Http\Requests\Api\V1\Person;

use App\Enums\DocumentType;
use App\Enums\PersonStatus;
use App\Enums\Sector;
use App\Enums\SpecialNeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('person')) ?? false;
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
            'document_type' => ['sometimes', Rule::in(DocumentType::values())],

            /**
             * Document number of the person.
             *
             * @example 123456789
             */
            'document_number' => ['sometimes', 'string', 'max:32', 'alpha_num'],

            /**
             * First name of the affected person.
             *
             * @example Maria Fernanda
             */
            'first_name' => ['sometimes', 'string', 'max:120'],

            /**
             * Last name of the affected person.
             *
             * @example Garcia Lopez
             */
            'last_name' => ['sometimes', 'string', 'max:120'],

            /**
             * Contact phone number (optional country code +57).
             *
             * @example 573001234567
             */
            'phone' => ['sometimes', 'string', 'max:24', 'regex:/^(\+?57)?3[0-9]{9}$/'],

            /**
             * Municipality where the person was located.
             *
             * @example Buenaventura
             */
            'municipality' => ['sometimes', 'string', 'max:120'],

            /**
             * Neighborhood or sector of the location.
             *
             * @example La Playita
             */
            'neighborhood' => ['nullable', 'string', 'max:120'],

            /**
             * Street address of the person.
             *
             * @example Calle 5 # 12-34
             */
            'address' => ['nullable', 'string', 'max:255'],

            /**
             * Area sector where the person is located.
             *
             * @example urban
             */
            'sector' => ['sometimes', Rule::in(Sector::values())],

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
             * Registration status of the person.
             *
             * @example verified
             */
            'status' => ['sometimes', Rule::enum(PersonStatus::class)],
        ];
    }
}
