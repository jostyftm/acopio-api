<?php

namespace App\Http\Requests\Api\V1\Permission;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('permissions.manage') ?? false;
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
             * Module the permission belongs to.
             *
             * @example 2
             */
            'module_id' => ['required', 'integer', 'exists:modules,id'],

            /**
             * Permission action that composes the name as {module}.{action}.
             *
             * @example export
             */
            'action' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9-]*$/'],

            /**
             * Human readable label shown in the frontend.
             *
             * @example Exportar
             */
            'display_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Ensures the composed permission name is unique for the api guard.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $name = $this->composedName();
            if ($name !== null && Permission::query()->where('name', $name)->where('guard_name', 'api')->exists()) {
                $validator->errors()->add('action', __('messages.permission_exists'));
            }
        });
    }

    protected function composedName(): ?string
    {
        $module = Module::query()->find($this->integer('module_id'));
        $action = $this->input('action');

        return $module !== null && is_string($action) ? "{$module->key}.{$action}" : null;
    }
}
