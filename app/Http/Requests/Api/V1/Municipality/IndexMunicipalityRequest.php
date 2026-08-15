<?php

namespace App\Http\Requests\Api\V1\Municipality;

use Illuminate\Foundation\Http\FormRequest;

class IndexMunicipalityRequest extends FormRequest
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
             * Search term over the normalized municipality name.
             *
             * @example medellin
             */
            'term' => ['nullable', 'string', 'max:120'],

            /**
             * Department name to filter by.
             *
             * @example ANTIOQUIA
             */
            'department' => ['nullable', 'string', 'max:120'],

            /**
             * Maximum number of records to return.
             *
             * @example 5000
             */
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ];
    }
}
