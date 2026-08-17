<?php

namespace App\Http\Requests\Api\V1\IncidentType;

use App\Enums\SeverityMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('incident_type')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('incident_types', 'code')->ignore($this->route('incident_type'))],
            'display_name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'severity_mode' => ['sometimes', Rule::in(SeverityMode::values())],
            'is_active' => ['sometimes', 'boolean'],
            'needs' => ['nullable', 'array', 'max:20'],
            'needs.*' => ['integer', Rule::exists('needs', 'id')],
        ];
    }
}
