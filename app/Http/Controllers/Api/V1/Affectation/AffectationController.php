<?php

namespace App\Http\Controllers\Api\V1\Affectation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Affectation\UpdateAffectationRequest;
use App\Http\Resources\Api\V1\Affectation\AffectationResource;
use App\Models\Affectation;
use App\Support\ApiResponse;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffectationController extends Controller
{
    /**
     * Lista las afectaciones registradas.
     *
     * Requiere permiso de visualización del módulo de afectaciones.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Affectation::class);

        $affectations = AffectationResource::collection(
            Affectation::query()
                ->with(['person.municipality', 'needs'])
                ->latest('id')
                ->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($affectations);
    }

    /**
     * Muestra el detalle de una afectación con la información de la persona.
     *
     * Requiere permiso de visualización del módulo de afectaciones.
     */
    public function show(Request $request, Affectation $affectation): JsonResponse
    {
        $this->authorize('view', $affectation);

        $affectation->load(['person.municipality', 'needs', 'evidence']);

        return ApiResponse::success(AffectationResource::make($affectation));
    }

    /**
     * Actualiza la afectación de una persona.
     *
     * Permite corregir la severidad, descripción, ubicación del incidente
     * (cuando el censo se hizo en un lugar distinto), sincronizar las
     * necesidades y agregar nuevas evidencias. Requiere permiso de
     * actualización del módulo de afectaciones.
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

        $affectation->load(['person.municipality', 'needs', 'evidence']);

        return ApiResponse::success(AffectationResource::make($affectation));
    }
}
