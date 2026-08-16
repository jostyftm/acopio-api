<?php

namespace App\Http\Requests\Api\V1\Module;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('module')) ?? false;
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
             * Parent module that groups this one.
             *
             * @example 7
             */
            'parent_id' => ['nullable', 'integer', 'exists:modules,id'],

            /**
             * Unique lowercase slug used to compose permission names.
             *
             * @example people
             */
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9-]*$/', Rule::unique('modules', 'key')->ignore($this->route('module'))],

            /**
             * Display name shown in the sidebar.
             *
             * @example Personas
             */
            'name' => ['required', 'string', 'max:120'],

            /**
             * Sorting position among siblings.
             *
             * @example 2
             */
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],

            /**
             * Frontend route the module maps to.
             *
             * @example /people
             */
            'path' => ['nullable', 'string', 'max:255'],

            /**
             * Lucide icon name used in the sidebar.
             *
             * @example Users
             */
            'icon' => ['nullable', 'string', 'max:100'],

            /**
             * Whether the module appears in the sidebar menu.
             */
            'display_sidebar' => ['boolean'],

            /**
             * Whether the module is enabled.
             */
            'is_active' => ['boolean'],
        ];
    }
}
