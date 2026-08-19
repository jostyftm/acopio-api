<?php

namespace App\Http\Requests\Api\V1\Affectation;

use App\Enums\CasualtyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCasualtyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('affectation')) ?? false;
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
             * Person linked to the casualty (nullable when unidentified).
             *
             * @example 42
             */
            'person_id' => ['nullable', 'integer', 'exists:people,id'],

            /**
             * Type of casualty.
             *
             * @example deceased
             */
            'type' => ['required', Rule::enum(CasualtyType::class)],

            /**
             * Specific cause for deceased casualties.
             *
             * @example 3
             */
            'cause_id' => ['nullable', 'integer', 'exists:casualty_causes,id', Rule::requiredIf(fn () => $this->input('type') === CasualtyType::Deceased->value), Rule::prohibitedIf(fn () => $this->input('type') === CasualtyType::Injured->value)],
        ];
    }
}
