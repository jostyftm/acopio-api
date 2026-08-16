<?php

namespace App\Http\Requests\Api\V1\Organization;

use App\Enums\OrganizationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('organizations.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_type_id' => ['sometimes', 'integer', 'exists:organization_types,id', Rule::exists('organization_types', 'id')->where('is_active', true)],
            'name' => ['sometimes', 'string', 'max:240'],
            'nit' => ['sometimes', 'nullable', 'string', 'max:40', Rule::unique('organizations', 'nit')->ignore($this->route('organization'))],
            'description' => ['sometimes', 'nullable', 'string'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:200'],
            'contact_email' => ['sometimes', 'nullable', 'string', 'email', 'max:240'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'status' => ['sometimes', Rule::enum(OrganizationStatus::class)],
        ];
    }
}
