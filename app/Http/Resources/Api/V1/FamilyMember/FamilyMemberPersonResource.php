<?php

namespace App\Http\Resources\Api\V1\FamilyMember;

use App\Http\Resources\Api\V1\Attachment\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyMemberPersonResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API compliant array.
     *
     * Lightweight person representation for family member context.
     * Includes only the fields needed by the frontend for displaying
     * family member information.
     *
     * @return array{id: int, type: string, attributes: array<string, mixed>, relationships: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'person',
            'attributes' => [
                'document_type' => $this->document_type?->value,
                'document_number' => $this->document_number,
                'full_name' => $this->full_name,
                'birth_date' => $this->birth_date?->format('Y-m-d'),
                'current_age' => $this->current_age,
            ],
            'relationships' => [
                'evidence' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
            ],
        ];
    }
}
