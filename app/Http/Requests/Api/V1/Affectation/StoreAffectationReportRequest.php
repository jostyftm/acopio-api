<?php

namespace App\Http\Requests\Api\V1\Affectation;

use App\Enums\SeverityMode;
use App\Models\Affectation;
use App\Models\IncidentType;
use App\Rules\EvidenceFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAffectationReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('report', Affectation::class) ?? false;
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
             * Incident type of the report.
             *
             * @example 1
             */
            'incident_type_id' => ['required', 'integer', Rule::exists('incident_types', 'id')],

            /**
             * Property types affected by the incident (hogar, edificio, etc.).
             *
             * @example [1,3]
             */
            'property_types' => ['required', 'array', 'min:1', 'max:10'],
            'property_types.*' => ['integer', Rule::exists('property_types', 'id')],

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
             * Address of the incident. Required if no GPS location.
             *
             * @example Calle 5 # 12-34
             */
            'address' => ['nullable', 'string', 'max:255'],

            /**
             * Latitude of the incident location. Required if no address.
             *
             * @example 3.4215987
             */
            'incident_latitude' => ['nullable', 'numeric', 'between:-90,90'],

            /**
             * Longitude of the incident location. Required if no address.
             *
             * @example -76.5232674
             */
            'incident_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            /**
             * Description of the damage.
             *
             * @example Techo parcialmente destruido
             */
            'description' => ['nullable', 'string', 'max:1000'],

            /**
             * Identifiers of the needs related to the incident, tied to the incident type.
             *
             * @example [1,2,3]
             */
            'needs' => ['nullable', 'array', 'max:20'],
            'needs.*' => ['integer', Rule::exists('needs', 'id')->whereIn('id', $this->needsForIncidentType())],

            /**
             * Evidence files of the damage (photos or videos).
             *
             * @example
             */
            'evidence' => ['nullable', 'array', 'max:'.config('evidence.max_files')],
            'evidence.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', new EvidenceFile],
        ];
    }

    /**
     * Configure the validator instance to cross-validate address and GPS location.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasAddress = filled($this->input('address'));
            $hasLocation = filled($this->input('incident_latitude')) && filled($this->input('incident_longitude'));

            if (! $hasAddress && ! $hasLocation) {
                $validator->errors()->add(
                    'address',
                    'La dirección es obligatoria si no se indica la ubicación GPS del incidente.',
                );
                $validator->errors()->add(
                    'incident_latitude',
                    'La ubicación GPS es obligatoria si no se indica una dirección.',
                );
            }
        });
    }

    /**
     * Needs allowed for the selected incident type.
     *
     * @return array<int, int>
     */
    private function needsForIncidentType(): array
    {
        $type = IncidentType::query()->with('needs')->find($this->integer('incident_type_id'));

        return $type === null
            ? [0]
            : $type->needs->pluck('id')->all();
    }
}
