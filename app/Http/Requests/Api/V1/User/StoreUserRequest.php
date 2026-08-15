<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
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
             * Full name of the user.
             *
             * @example Ana Torres
             */
            'name' => ['required', 'string', 'max:240'],

            /**
             * Work email of the user.
             *
             * @example ana.torres@example.com
             */
            'email' => ['required', 'string', 'email', 'max:240', 'unique:users,email'],

            /**
             * Temporary password for the user.
             *
             * @example secret123
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            /**
             * Role assigned to the user.
             *
             * @example operator
             */
            'role' => ['required', Rule::in(['admin', 'operator', 'viewer'])],
        ];
    }
}
