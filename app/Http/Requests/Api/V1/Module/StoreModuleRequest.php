<?php

namespace App\Http\Requests\Api\V1\Module;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Module::class) ?? false;
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
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9-]*$/', Rule::unique('modules', 'key')],

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

            /**
             * Whether to create the individual CRUD permissions for the module.
             */
            'create_crud' => ['sometimes', 'boolean'],

            /**
             * Permissions to create when `create_crud` is enabled.
             */
            'permissions' => Rule::when(
                $this->boolean('create_crud'),
                ['required', 'array'],
                ['nullable', 'array'],
            ),

            /**
             * Permission action that composes the name as {module}.{action}.
             *
             * @example view
             */
            'permissions.*.action' => ['required_with:permissions', 'string', 'max:80', 'regex:/^[a-z][a-z0-9-]*$/'],

            /**
             * Human readable label shown in the frontend.
             *
             * @example Ver
             */
            'permissions.*.display_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
