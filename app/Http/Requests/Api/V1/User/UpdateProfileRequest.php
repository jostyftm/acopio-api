<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $user = $this->user();

        $emailRule = $user?->provider !== null
            ? Rule::in([$user->email])
            : Rule::unique('users', 'email')->ignore($user?->id);

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
            'email' => ['required', 'string', 'email', 'max:240', $emailRule],
        ];
    }
}
