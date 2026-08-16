<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
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
             * Current password of the user account.
             *
             * @example password
             */
            'current_password' => ['required', 'string'],

            /**
             * New password of the user account.
             *
             * @example new-password
             */
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
