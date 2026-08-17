<?php

namespace App\Http\Requests\Api\V1\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationCoverageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('organization')) ?? false;
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
             * Coverage zones assigned to the organization.
             *
             * @example [1,3]
             */
            'coverage_zone_ids' => ['required', 'array', 'min:1'],
            'coverage_zone_ids.*' => ['integer', Rule::exists('coverage_zones', 'id')],
        ];
    }
}
