<?php

namespace App\Http\Requests\Api\V1\Facility;

use App\Enums\FacilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $facility = $this->route('facility');

        if ($user === null || $facility === null || ! $user->can('update', $facility)) {
            return false;
        }

        if ($user->hasRole('admin', 'api')) {
            return true;
        }

        $inOwnOrg = (int) $facility->organization_id === (int) $user->organization_id;

        if (! $inOwnOrg) {
            return false;
        }

        return $this->input('organization_id') === null
            || (int) $this->input('organization_id') === (int) $user->organization_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'facility_type_id' => ['sometimes', 'integer', 'exists:facility_types,id', Rule::exists('facility_types', 'id')->where('is_active', true)],
            'name' => ['sometimes', 'string', 'max:240'],
            'municipality_id' => ['sometimes', 'nullable', 'integer', 'exists:municipalities,id'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'available' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(FacilityStatus::class)],
        ];
    }
}
