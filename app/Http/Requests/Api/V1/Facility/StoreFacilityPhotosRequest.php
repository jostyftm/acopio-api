<?php

namespace App\Http\Requests\Api\V1\Facility;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityPhotosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $facility = $this->route('facility');

        return $this->user()?->can('update', $facility) ?? false;
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
             * Photos of the facility (max 10 images, 10 MB each).
             *
             * @example []
             */
            'photos' => ['required', 'array', 'max:10'],
            'photos.*' => ['required', 'image', 'max:10240'],
        ];
    }
}
