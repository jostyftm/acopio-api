<?php

namespace App\Http\Requests\Api\V1\Need;

use App\Models\Need;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNeedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('needs.manage') ?? false;
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
             * Display name of the need.
             *
             * @example Pañales
             */
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('needs', 'name'),
                function (string $attribute, mixed $value, callable $fail): void {
                    if (Need::query()->where('normalized_name', Need::normalizeName((string) $value))->exists()) {
                        $fail('La necesidad ya existe.');
                    }
                },
            ],

            /**
             * Optional description of the need.
             *
             * @example Pañales para bebés y adultos mayores
             */
            'description' => ['nullable', 'string', 'max:500'],

            /**
             * Impact level of the need, used to group it in the form.
             *
             * @example 1
             */
            'severity_need_id' => ['required', 'integer', Rule::exists('severity_needs', 'id')],
        ];
    }
}
