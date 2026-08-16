<?php

namespace App\Http\Requests\Api\V1\AffectationSeverity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAffectationSeverityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('affectation_severity')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'incident_type_id' => ['sometimes', 'integer', Rule::exists('incident_types', 'id')],
            'code' => ['sometimes', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('affectation_severities', 'code')->ignore($this->route('affectation_severity'))],
            'display_name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
