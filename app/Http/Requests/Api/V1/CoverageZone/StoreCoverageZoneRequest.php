<?php

namespace App\Http\Requests\Api\V1\CoverageZone;

use App\Models\CoverageZone;
use App\Rules\ValidPolygon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoverageZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', CoverageZone::class) ?? false;
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
            'name' => ['required', 'string', 'max:150'],

            /**
             * Municipality where the zone is located.
             *
             * @example 1
             */
            'municipality_id' => ['required', 'integer', Rule::exists('municipalities', 'id')],

            /**
             * Polygon of the zone as an array of [longitude, latitude] pairs (EPSG:4326).
             *
             * @example [{"0":-76.523,"1":3.421},{"0":-76.520,"1":3.421},{"0":-76.520,"1":3.424}]
             */
            'polygon' => ['required', 'array', 'min:3', new ValidPolygon],
            'polygon.*' => ['required', 'array', 'size:2'],
            'polygon.*.*' => ['required', 'numeric', 'between:-180,180'],

            /**
             * Map center as [longitude, latitude] used when the zone is viewed.
             *
             * @example {"lng":-76.523,"lat":3.421}
             */
            'map_center' => ['required', 'array'],
            'map_center.lng' => ['required', 'numeric', 'between:-180,180'],
            'map_center.lat' => ['required', 'numeric', 'between:-90,90'],

            /**
             * Zoom level used when the zone is viewed.
             *
             * @example 12
             */
            'map_zoom' => ['required', 'numeric', 'between:0,22'],
        ];
    }
}
