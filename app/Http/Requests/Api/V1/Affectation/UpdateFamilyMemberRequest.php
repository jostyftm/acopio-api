<?php

namespace App\Http\Requests\Api\V1\Affectation;

use App\Rules\EvidenceFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFamilyMemberRequest extends FormRequest
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
             * Document type of the family member.
             *
             * @example CC
             */
            'document_type' => ['sometimes', 'string', Rule::in(['CC', 'TI', 'CE', 'RC', 'MS', 'AS'])],

            /**
             * Document number of the family member.
             *
             * @example 1234567890
             */
            'document_number' => ['sometimes', 'string', 'max:20'],

            /**
             * First name of the family member.
             *
             * @example Juan
             */
            'first_name' => ['sometimes', 'string', 'max:100'],

            /**
             * Last name of the family member.
             *
             * @example Pérez
             */
            'last_name' => ['sometimes', 'string', 'max:100'],

            /**
             * Birth date of the family member.
             *
             * @example 1980-05-15
             */
            'birth_date' => ['nullable', 'date', 'before:today'],

            /**
             * Whether this family member is the householder.
             *
             * @example true
             */
            'is_householder' => ['nullable', 'boolean'],

            /**
             * Family group number.
             *
             * @example 1
             */
            'family_group' => ['sometimes', 'integer', 'min:1'],

            /**
             * Evidence files for the family member (photos).
             */
            'evidence' => ['nullable', 'array', 'max:'.config('evidence.max_files', 10)],
            'evidence.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif', new EvidenceFile],
        ];
    }
}
