<?php

namespace App\Http\Controllers;

use App\Actions\CreateScan;
use App\Enums\ScanSource;
use App\Enums\SchoolType;
use App\Http\Requests\ShowScanRequest;
use App\Http\Requests\StoreScanRequest;
use App\Models\Scan;
use App\Services\CorrectionModePresenter;
use App\Support\LingoLens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScanController extends Controller
{
    /**
     * History ("Verlauf"): all scans of the student, newest first.
     */
    public function index(Request $request): View
    {
        $scans = $request->user()
            ->scans()
            ->withCount('corrections')
            ->latest()
            ->latest('id')
            ->paginate(20);

        return view('scans.index', compact('scans'));
    }

    /**
     * Setup & upload form, pre-filled with the student's school profile.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        $schoolType = SchoolType::tryFrom((string) old('school_type')) ?? $user->school_type;
        $department = old('department', $user->department ?? config('lingolens.departments')[0]);
        $textType = old('text_type', $schoolType->defaultTextType());
        $textTypes = config('lingolens.text_types');
        $departments = config('lingolens.departments');
        $extensions = ScanSource::allowedExtensions();
        $accept = collect($extensions)->map(fn (string $extension) => '.'.$extension)
            ->push('application/pdf', 'image/*', 'text/plain', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->implode(',');
        $photoFormats = ScanSource::photoFormatsLabel();
        $demoMode = LingoLens::demoMode();
        $remainingScans = $user->remainingScansToday();

        return view('scans.create', compact('schoolType', 'department', 'textType', 'textTypes', 'departments', 'extensions', 'accept', 'photoFormats', 'demoMode', 'remainingScans'));
    }

    public function store(StoreScanRequest $request, CreateScan $createScan): RedirectResponse
    {
        $scan = $createScan->handle($request->user(), $request->validated(), $request->file('file'));

        return redirect()->route('scans.processing', $scan);
    }

    /**
     * Processing screen; polls the status until the queue job is done.
     */
    public function processing(Scan $scan): View|RedirectResponse
    {
        Gate::authorize('view', $scan);

        if ($scan->isCompleted()) {
            return redirect()->route('scans.show', $scan);
        }

        return view('scans.processing', compact('scan'));
    }

    /**
     * JSON endpoint polled by the processing screen.
     */
    public function status(Scan $scan): JsonResponse
    {
        Gate::authorize('view', $scan);

        return response()->json([
            'status' => $scan->status->value,
            'progress_step' => $scan->progress_step,
            'percent' => $scan->progressPercent(),
            'redirect_url' => $scan->isCompleted() ? route('scans.show', $scan) : null,
        ]);
    }

    /**
     * Streams the uploaded photo while it still exists (it is deleted after processing).
     * Documents (PDF, Word, TXT) are never served back.
     */
    public function preview(Scan $scan): StreamedResponse
    {
        Gate::authorize('view', $scan);

        abort_unless(
            $scan->source->isPhoto() && $scan->file_path && Storage::disk('local')->exists($scan->file_path),
            404
        );

        return Storage::disk('local')->response($scan->file_path, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Split-screen correction mode.
     */
    public function show(ShowScanRequest $request, Scan $scan, CorrectionModePresenter $presenter): View|RedirectResponse
    {
        if (! $scan->isCompleted()) {
            return redirect()->route('scans.processing', $scan);
        }

        $correctionMode = $presenter->present($scan, $request->errorType());

        return view('scans.show', compact('scan', 'correctionMode'));
    }
}
