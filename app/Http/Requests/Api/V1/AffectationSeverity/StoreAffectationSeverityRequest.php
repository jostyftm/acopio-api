<?php

namespace App\Http\Requests\Api\V1\AffectationSeverity;

use App\Models\AffectationSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAffectationSeverityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', AffectationSeverity::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'incident_type_id' => ['required', 'integer', Rule::exists('incident_types', 'id')],
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('affectation_severities', 'code')->where('incident_type_id', $this->integer('incident_type_id'))],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
