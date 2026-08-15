<?php

namespace App\Http\Resources\Api\V1\Person;

use App\Enums\PersonStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'person',
            'attributes' => $this->getAttributes(),
            'relationships' => $this->getRelationships(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        $attributes = [
            'full_name' => $this->full_name,
            'masked_document_number' => $this->maskDocumentNumber(),
            'municipality' => $this->municipality,
            'neighborhood' => $this->neighborhood,
            'status' => $this->status?->value,
            'created_at' => $this->created_at,
        ];

        if ($this->status === PersonStatus::Located) {
            $attributes['latitude'] = $this->latitude;
            $attributes['longitude'] = $this->longitude;
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRelationships(): array
    {
        return [];
    }

    private function maskDocumentNumber(): string
    {
        $length = strlen((string) $this->document_number);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($this->document_number, -4);
    }
}
