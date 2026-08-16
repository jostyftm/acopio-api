<?php

namespace App\Http\Requests\Api\V1\Registration;

use App\Enums\DocumentType;
use App\Enums\Sector;
use App\Enums\SeverityMode;
use App\Enums\SpecialNeed;
use App\Models\IncidentType;
use App\Rules\EvidenceFile;
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
             * Birth date of the affected person.
             *
             * @example 1990-05-10
             */
            'birth_date' => ['required', 'date', 'before_or_equal:today'],

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
             * DIVIPOLA municipality code for unambiguous resolution.
             *
             * @example 76109
             */
            'municipality_code' => ['nullable', 'string', 'max:5'],

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
            'sector' => ['required', Rule::in(Sector::values())],

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
             * Incident type of the affectation.
             *
             * @example 1
             */
            'incident_type_id' => ['required', 'integer', Rule::exists('incident_types', 'id')],

            /**
             * Severities of the affectation, tied to the incident type.
             *
             * @example [1]
             */
            'severities' => [
                'required',
                'array',
                'min:1',
                'max:10',
                function (string $attribute, mixed $value, callable $fail): void {
                    $type = IncidentType::query()->find($this->integer('incident_type_id'));

                    if ($type !== null && $type->severity_mode === SeverityMode::Single && count($value) > 1) {
                        $fail('El tipo de incidente solo permite una gravedad.');
                    }
                },
            ],
            'severities.*' => ['integer', Rule::exists('affectation_severities', 'id')->where('incident_type_id', $this->integer('incident_type_id'))],

            /**
             * Property types affected by the incident (vivienda, negocio, etc.).
             *
             * @example [1,3]
             */
            'property_types' => ['nullable', 'array', 'max:10'],
            'property_types.*' => ['integer', Rule::exists('property_types', 'id')],

            /**
             * Description of the damage.
             *
             * @example Techo parcialmente destruido
             */
            'description' => ['nullable', 'string', 'max:1000'],

            /**
             * Latitude of the incident location (optional, may differ from census).
             *
             * @example 3.4215987
             */
            'incident_latitude' => ['nullable', 'numeric', 'between:-90,90'],

            /**
             * Longitude of the incident location (optional, may differ from census).
             *
             * @example -76.5232674
             */
            'incident_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            /**
             * Identifiers of the needs related to the affectation.
             *
             * @example [1,2,3]
             */
            'needs' => ['nullable', 'array', 'max:20'],
            'needs.*' => ['integer', Rule::exists('needs', 'id')],

            /**
             * Evidence files of the damage (photos or videos).
             *
             * @example
             */
            'evidence' => ['nullable', 'array', 'max:'.config('evidence.max_files')],
            'evidence.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', new EvidenceFile],

            /**
             * Members of the affected person's family group.
             *
             * @example
             */
            'family_members' => ['nullable', 'array', 'max:20'],

            /**
             * Document type of the family member.
             *
             * @example CC
             */
            'family_members.*.document_type' => ['required', Rule::in(DocumentType::values())],

            /**
             * Document number of the family member.
             *
             * @example 123456789
             */
            'family_members.*.document_number' => ['required', 'string', 'max:32', 'alpha_num'],

            /**
             * First name of the family member.
             *
             * @example Juan
             */
            'family_members.*.first_name' => ['required', 'string', 'max:120'],

            /**
             * Last name of the family member.
             *
             * @example Perez
             */
            'family_members.*.last_name' => ['required', 'string', 'max:120'],

            /**
             * Birth date of the family member.
             *
             * @example 2010-03-15
             */
            'family_members.*.birth_date' => ['required', 'date', 'before_or_equal:today'],

            /**
             * Whether the family member is the household head.
             *
             * @example false
             */
            'family_members.*.is_householder' => ['sometimes', 'boolean'],

            /**
             * Evidence files of the family member.
             *
             * @example
             */
            'family_members.*.evidence' => ['nullable', 'array', 'max:'.config('evidence.max_files')],
            'family_members.*.evidence.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', new EvidenceFile],

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
