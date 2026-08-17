<?php

namespace App\Http\Requests\Api\V1\Organization;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
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
             * Organization type catalog reference.
             *
             * @example 1
             */
            'organization_type_id' => ['required', 'integer', 'exists:organization_types,id', Rule::exists('organization_types', 'id')->where('is_active', true)],

            /**
             * Municipality where the organization operates.
             *
             * @example 1024
             */
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],

            /**
             * Legal or common name of the organization.
             *
             * @example Fundación Esperanza
             */
            'name' => ['required', 'string', 'max:240'],

            /**
             * NIT or equivalent tax document.
             *
             * @example 901123456-0
             */
            'nit' => ['nullable', 'string', 'max:40', 'unique:organizations,nit'],

            /**
             * Mission, purpose or description.
             *
             * @example Apoyo a personas afectadas por emergencias
             */
            'description' => ['nullable', 'string'],

            /**
             * Contact person name.
             *
             * @example Ana Torres
             */
            'contact_name' => ['nullable', 'string', 'max:200'],

            /**
             * Contact email.
             *
             * @example ana@fundacion.test
             */
            'contact_email' => ['nullable', 'string', 'email', 'max:240'],

            /**
             * Contact phone.
             *
             * @example +573001112233
             */
            'contact_phone' => ['nullable', 'string', 'max:40'],

            /**
             * Organization status.
             *
             * @example active
             */
            'status' => ['required', Rule::enum(OrganizationStatus::class)],
        ];
    }
}
