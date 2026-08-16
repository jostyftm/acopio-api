<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $target = $this->route('user');

        if ($user === null || ! $user->can('users.manage')) {
            return false;
        }

        if ($user->hasRole('admin', 'api')) {
            return true;
        }

        return $target !== null
            && (int) $target->organization_id === (int) $user->organization_id
            && ($this->input('organization_id') === null
                || (int) $this->input('organization_id') === (int) $user->organization_id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowedRoles = $this->user()?->hasRole('admin', 'api') === true
            ? ['admin', 'org_admin', 'operator', 'viewer']
            : ['operator', 'viewer'];

        return [
            /**
             * Full name of the user.
             *
             * @example Ana Torres
             */
            'name' => ['sometimes', 'string', 'max:240'],

            /**
             * Work email of the user.
             *
             * @example ana.torres@example.com
             */
            'email' => ['sometimes', 'string', 'email', 'max:240', Rule::unique('users', 'email')->ignore($this->route('user'))],

            /**
             * New password for the user.
             *
             * @example secret123
             */
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],

            /**
             * Role assigned to the user.
             *
             * @example operator
             */
            'role' => ['sometimes', Rule::in($allowedRoles)],

            /**
             * Organization the user belongs to.
             *
             * @example 1
             */
            'organization_id' => ['sometimes', 'nullable', 'integer', 'exists:organizations,id'],

            /**
             * Whether the account can log in.
             *
             * @example true
             */
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
