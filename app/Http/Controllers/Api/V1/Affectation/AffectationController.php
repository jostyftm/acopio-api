<?php

namespace App\Http\Controllers\Api\V1\Affectation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Affectation\UpdateAffectationRequest;
use App\Http\Resources\Api\V1\Affectation\AffectationResource;
use App\Models\Affectation;
use App\Support\ApiResponse;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Http\JsonResponse;

class AffectationController extends Controller
{
    /**
     * Actualiza la afectación de una persona.
     *
     * Permite corregir la severidad, descripción, ubicación del incidente
     * (cuando el censo se hizo en un lugar distinto), sincronizar las
     * necesidades y agregar nuevas evidencias. Requiere permiso de
     * actualización de personas.
     */
    public function update(UpdateAffectationRequest $request, Affectation $affectation): JsonResponse
    {
        $this->authorize('update', $affectation);

        $data = [
            'severity' => $request->input('severity'),
            'description' => $request->input('description'),
        ];

        if ($request->has('latitude') || $request->has('longitude')) {
            $latitude = $request->filled('latitude') ? (float) $request->input('latitude') : null;
            $longitude = $request->filled('longitude') ? (float) $request->input('longitude') : null;

            $data['location'] = $latitude !== null && $longitude !== null
                ? Point::makeGeodetic($latitude, $longitude)
                : null;
        }

        $affectation->update($data);

        if ($request->filled('needs')) {
            $affectation->needs()->sync($request->input('needs'));
        }

        foreach ($request->file('evidence', []) as $file) {
            $path = $file->store('evidence/affectations/'.$affectation->id, 's3');

            $affectation->evidence()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $affectation->load(['needs', 'evidence']);

        return ApiResponse::success(AffectationResource::make($affectation));
    }
}
