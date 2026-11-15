<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Answer to a CSRF token mismatch ("Page Expired", HTTP 419).
 *
 * Instead of Laravel's dead-end page the user goes back to the form, with the
 * typed fields restored and a hint to pick the photo again. The case is logged
 * without any content, so the cause can be found (e.g. a token that never
 * arrived because the request body was lost on the way).
 */
class SessionExpiredResponse
{
    /** Never flashed back into the session. */
    private const EXCLUDED = ['_token', 'password', 'password_confirmation', 'current_password', 'file', 'task_file'];

    public const MESSAGE = 'Die Seite war zu lange geöffnet oder die Verbindung wurde unterbrochen. Deine Eingaben sind wieder da – '
        .'bitte wähle das Foto bzw. die Datei noch einmal aus und sende erneut ab.';

    public static function make(Request $request): ?RedirectResponse
    {
        Log::warning('CSRF token mismatch (Page Expired)', [
            'path' => $request->path(),
            'token_sent' => $request->filled('_token') || $request->hasHeader('X-CSRF-TOKEN'),
            'session_has_token' => $request->hasSession() && $request->session()->has('_token'),
            'authenticated' => $request->user() !== null,
            'content_length' => (int) $request->server('CONTENT_LENGTH'),
            'fields' => count($request->request->all()),
            'files' => count($request->allFiles()),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 160),
        ]);

        // API-like requests keep Laravel's default answer.
        if ($request->expectsJson()) {
            return null;
        }

        return redirect()->back()
            ->withInput($request->except(self::EXCLUDED))
            ->withErrors(['session' => self::MESSAGE]);
    }
}
