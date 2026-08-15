<?php

namespace App\Http\Requests\Api\V1\Person;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('verify', $this->route('person')) ?? false;
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
        ];
    }
}
