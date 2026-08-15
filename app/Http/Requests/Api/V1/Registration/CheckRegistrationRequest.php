<?php

namespace App\Http\Requests\Api\V1\Registration;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
             * Document type of the person.
             *
             * @example CC
             */
            'document_type' => ['required', Rule::in(DocumentType::values())],

            /**
             * Document number of the person.
             *
             * @example 123456789
             */
            'document_number' => ['required', 'string', 'max:32', 'alpha_num'],
        ];
    }
}
