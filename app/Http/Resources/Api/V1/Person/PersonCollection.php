<?php

namespace App\Http\Resources\Api\V1\Person;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PersonCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn ($resource) => (new PersonResource($resource))->toArray($request)),
        ];
    }
}
