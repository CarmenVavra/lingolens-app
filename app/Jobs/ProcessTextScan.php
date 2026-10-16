<?php

namespace App\Jobs;

use App\Actions\StoreScanFeedback;
use App\Enums\ScanStatus;
use App\Exceptions\ScanProcessingException;
use App\Models\Scan;
use App\Services\Correction\TextCorrector;
use App\Services\Extraction\DocumentTextExtractor;
use App\Support\DemoSamples;
use App\Support\LingoLens;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Asynchronous analysis of a scan: read the text (OCR for photos and scanned
 * PDFs, text layer for typed PDFs, Word/TXT directly) → AI correction (GPT-4o)
 * → persist feedback. Runs in the queue to avoid HTTP timeouts.
 *
 * progress_step tracks the active step of the processing screen:
 * 1 = reading the text, 2 = AI correction, 3 = saving, 4 = done.
 */
class ProcessTextScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public Scan $scan) {}

    public function handle(DocumentTextExtractor $extractor, TextCorrector $corrector, StoreScanFeedback $storeFeedback): void
    {
        $scan = $this->scan->refresh();

        // Already finished or given up (e.g. by scans:fail-stale).
        if (! $scan->isProcessing()) {
            return;
        }

        // Also covers the case where the job runs right after the HTTP response.
        @set_time_limit($this->timeout);

        try {
            // 1. Read the text
            $this->pause();
            ['text' => $text, 'method' => $method] = $this->readText($extractor);

            if (mb_strlen($text) < (int) config('lingolens.min_ocr_characters')) {
                throw new ScanProcessingException($scan->source->isPhoto()
                    ? 'Die Handschrift-Erkennung hat zu wenig Text gefunden.'
                    : 'In der Datei wurde zu wenig Text gefunden.');
            }

            if (mb_strlen($text) > (int) config('lingolens.max_text_characters')) {
                throw new ScanProcessingException('Der Text ist zu lang. LingoLens korrigiert Texte bis etwa 2.000 Wörter.');
            }

            $scan->update([
                'raw_extracted_text' => $text,
                'word_count' => str_word_count($text),
                'extraction_method' => $method,
                'progress_step' => 2,
            ]);

            // 2. AI correction
            $this->pause();
            $result = $corrector->correct($scan, $text);
            $scan->update(['progress_step' => 3]);

            // 3. Persist feedback and corrections
            $this->pause();
            $storeFeedback->handle($scan, $result);

            $scan->update([
                'title' => $result['title'] !== '' ? $result['title'] : null,
                'status' => ScanStatus::Completed,
                'progress_step' => Scan::TOTAL_STEPS,
            ]);
        } catch (Throwable $e) {
            $scan->update([
                'status' => ScanStatus::Failed,
                'failure_reason' => $e instanceof ScanProcessingException ? $e->getMessage() : 'Die Analyse ist fehlgeschlagen.',
            ]);

            if (! $e instanceof ScanProcessingException) {
                report($e);
            }
        } finally {
            // Uploads are only kept while they are being processed.
            if ($scan->file_path) {
                Storage::disk('local')->delete($scan->file_path);
                $scan->update(['file_path' => null]);
            }
        }
    }

    /**
     * @return array{text: string, method: string}
     */
    private function readText(DocumentTextExtractor $extractor): array
    {
        // Demo mode: the sample feedback only fits the sample texts.
        if ($this->scan->uses_sample || LingoLens::demoMode()) {
            return ['text' => DemoSamples::forTextType($this->scan->text_type)['text'], 'method' => 'sample'];
        }

        $contents = $this->scan->file_path ? Storage::disk('local')->get($this->scan->file_path) : null;

        if ($contents === null) {
            throw new ScanProcessingException('Die hochgeladene Datei wurde nicht gefunden.');
        }

        return $extractor->extract($this->scan, $contents);
    }

    /**
     * In demo mode the steps would finish instantly; a short pause lets the
     * processing screen show each step.
     */
    private function pause(): void
    {
        $delay = (int) config('lingolens.demo_step_delay_ms');

        if ($delay > 0 && LingoLens::demoMode()) {
            usleep($delay * 1000);
        }
    }
}
