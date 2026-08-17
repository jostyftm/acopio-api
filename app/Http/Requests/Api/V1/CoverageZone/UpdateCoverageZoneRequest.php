<?php

namespace App\Http\Requests\Api\V1\CoverageZone;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoverageZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('coverage_zone')) ?? false;
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
             * Name of the coverage zone.
             *
             * @example Zona Centro
             */
            'name' => ['sometimes', 'string', 'max:150'],

            /**
             * Municipality where the zone is located.
             *
             * @example 1
             */
            'municipality_id' => ['sometimes', 'integer', Rule::exists('municipalities', 'id')],

            /**
             * Polygon of the zone in WKT format (EPSG:4326, longitude latitude order).
             *
             * @example POLYGON((-76.523 3.421, -76.520 3.421, -76.520 3.424, -76.523 3.421))
             */
            'polygon' => ['sometimes', 'string', 'regex:/^POLYGON\(\(.*\)\)$/i'],
        ];
    }
}
