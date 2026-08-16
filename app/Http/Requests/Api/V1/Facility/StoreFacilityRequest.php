<?php

namespace App\Http\Requests\Api\V1\Facility;

use App\Enums\FacilityStatus;
use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('create', Facility::class)) {
            return false;
        }

        if ($user->hasRole('admin', 'api')) {
            return true;
        }

        return $this->input('organization_id') !== null
            && (int) $this->input('organization_id') === (int) $user->organization_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'facility_type_id' => ['required', 'integer', 'exists:facility_types,id', Rule::exists('facility_types', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:240'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'available' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(FacilityStatus::class)],
        ];
    }
}
