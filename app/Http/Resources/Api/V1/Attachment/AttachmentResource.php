<?php

namespace App\Http\Resources\Api\V1\Attachment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API compliant array.
     *
     * @return array{id: int, type: string, attributes: array<string, mixed>, relationships: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'attachment',
            'attributes' => [
                'url' => $this->url,
                'original_name' => $this->original_name,
                'mime' => $this->mime,
            ],
            'relationships' => [],
        ];
    }
}
