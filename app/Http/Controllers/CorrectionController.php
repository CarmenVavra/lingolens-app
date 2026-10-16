<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCorrectionRequest;
use App\Models\Correction;
use Illuminate\Http\JsonResponse;

class CorrectionController extends Controller
{
    /**
     * Persist the "verstanden" state; it feeds the Matura-Ready-Score.
     */
    public function update(UpdateCorrectionRequest $request, Correction $correction): JsonResponse
    {
        $correction->update([
            'understood_at' => $request->boolean('understood') ? ($correction->understood_at ?? now()) : null,
        ]);

        return response()->json([
            'id' => $correction->id,
            'understood' => $correction->understood_at !== null,
        ]);
    }
}
