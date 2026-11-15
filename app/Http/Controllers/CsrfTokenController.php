<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Hands out the current CSRF token of the session. The upload form asks for
 * it right before submitting, so a page that stayed open for a long time (or
 * was restored by the phone) never sends an outdated token ("Page Expired").
 */
class CsrfTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->session()->token();

        return response()->json(compact('token'))->header('Cache-Control', 'no-store, private');
    }
}
