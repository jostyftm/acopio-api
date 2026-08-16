<?php

namespace App\Http\Requests\Api\V1\Affectation;

use App\Enums\SeverityMode;
use App\Models\IncidentType;
use App\Rules\EvidenceFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAffectationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('affectation')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $incidentTypeId = $this->input('incident_type_id', $this->route('affectation')?->incident_type_id);

        return [
            /**
             * Incident type of the affectation.
             *
             * @example 1
             */
            'incident_type_id' => ['sometimes', 'integer', Rule::exists('incident_types', 'id')],

            /**
             * Severities of the affectation, tied to the incident type.
             *
             * @example [1]
             */
            'severities' => [
                'sometimes',
                'array',
                'min:1',
                'max:10',
                function (string $attribute, mixed $value, callable $fail) use ($incidentTypeId): void {
                    $type = $incidentTypeId !== null ? IncidentType::query()->find($incidentTypeId) : null;

                    if ($type !== null && $type->severity_mode === SeverityMode::Single && count($value) > 1) {
                        $fail('El tipo de incidente solo permite una gravedad.');
                    }
                },
            ],
            'severities.*' => ['integer', Rule::exists('affectation_severities', 'id')->where('incident_type_id', $incidentTypeId)],

            /**
             * Property types affected by the incident.
             *
             * @example [1,3]
             */
            'property_types' => ['sometimes', 'array', 'max:10'],
            'property_types.*' => ['integer', Rule::exists('property_types', 'id')],

            /**
             * Description of the damage.
             *
             * @example Vivienda destruida por la inundación
             */
            'description' => ['nullable', 'string', 'max:1000'],

            /**
             * Latitude of the incident location.
             *
             * @example 6.2567185
             */
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

            /**
             * Longitude of the incident location.
             *
             * @example -75.6151100
             */
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            /**
             * Identifiers of the needs related to the affectation.
             *
             * @example [1,2,3]
             */
            'needs' => ['nullable', 'array', 'max:20'],
            'needs.*' => ['integer', Rule::exists('needs', 'id')],

            /**
             * New evidence files to attach (photos or videos).
             *
             * @example
             */
            'evidence' => ['nullable', 'array', 'max:'.config('evidence.max_files')],
            'evidence.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', new EvidenceFile],
        ];
    }
}
