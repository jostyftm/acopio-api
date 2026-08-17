<?php

namespace App\Http\Requests\Api\V1\Affectation;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAffectationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('verify', $this->route('affectation')) ?? false;
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
             * Latitude of the verified location (optional).
             *
             * @example 3.4215987
             */
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

            /**
             * Longitude of the verified location (optional).
             *
             * @example -76.5232674
             */
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
