<?php

namespace App\Http\Requests\Api\V1\Affectation;

use App\Enums\AffectationSeverity;
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
        return [
            /**
             * Severity of the damage.
             *
             * @example total
             */
            'severity' => ['sometimes', Rule::in(AffectationSeverity::values())],

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
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', new EvidenceFile],
        ];
    }
}
