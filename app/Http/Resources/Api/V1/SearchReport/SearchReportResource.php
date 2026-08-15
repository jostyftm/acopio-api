<?php

namespace App\Http\Resources\Api\V1\SearchReport;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchReportResource extends JsonResource
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
            'type' => 'search_report',
            'attributes' => $this->getAttributes(),
            'relationships' => $this->getRelationships(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return [
            'searched_name' => $this->searched_name,
            'document_type' => $this->document_type?->value,
            'document_number' => $this->document_number,
            'municipality' => $this->municipality,
            'reporter_name' => $this->reporter_name,
            'reporter_phone' => $this->reporter_phone,
            'relationship' => $this->relationship,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'located_at' => $this->located_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRelationships(): array
    {
        return [
            'person' => $this->whenLoaded('person', fn (): ?array => $this->person?->only(['id', 'first_name', 'last_name', 'full_name'])),
            'handled_by' => $this->whenLoaded('handledBy', fn (): ?array => $this->handledBy?->only(['id', 'name'])),
        ];
    }
}
