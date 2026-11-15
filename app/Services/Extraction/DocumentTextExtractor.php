<?php

namespace App\Services\Extraction;

use App\Enums\ScanSource;
use App\Models\Scan;
use App\Services\Ocr\TextRecognizer;
use App\Support\TextNormalizer;

/**
 * Gets the student text out of an upload, whatever its format:
 *
 * - photo → OCR (handwriting or print)
 * - PDF typed on a laptop → text layer, no OCR needed
 * - scanned / handwritten PDF (no text layer) → OCR of the PDF pages
 * - Word / TXT → document text
 */
class DocumentTextExtractor
{
    public const METHOD_OCR = 'ocr';

    public const METHOD_TEXT_LAYER = 'text_layer';

    public const METHOD_DOCUMENT = 'document';

    public function __construct(
        private readonly TextRecognizer $ocr,
        private readonly PdfTextReader $pdf,
        private readonly WordTextReader $word,
    ) {}

    /**
     * @return array{text: string, method: string}
     */
    public function extract(Scan $scan, string $contents): array
    {
        return $this->read($scan, $scan->source, $contents, task: false);
    }

    /**
     * The optional task prompt ("Aufgabenstellung") – usually a printed worksheet.
     */
    public function extractTask(Scan $scan, ScanSource $source, string $contents): string
    {
        return $this->read($scan, $source, $contents, task: true)['text'];
    }

    /**
     * @return array{text: string, method: string}
     */
    private function read(Scan $scan, ScanSource $source, string $contents, bool $task): array
    {
        [$text, $method] = match ($source) {
            ScanSource::Photo => [$this->ocr->recognizeImage($scan, $contents, $task), self::METHOD_OCR],
            ScanSource::Pdf => $this->fromPdf($scan, $contents, $task),
            ScanSource::Word => [$this->word->read($contents), self::METHOD_DOCUMENT],
            ScanSource::Text => [$contents, self::METHOD_DOCUMENT],
        };

        return ['text' => TextNormalizer::normalize($text), 'method' => $method];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fromPdf(Scan $scan, string $contents, bool $task): array
    {
        $layer = $this->pdf->read($contents);

        if (TextNormalizer::letterCount($layer) >= (int) config('lingolens.min_text_layer_letters')) {
            return [$layer, self::METHOD_TEXT_LAYER];
        }

        return [$this->ocr->recognizePdf($scan, $contents, $task), self::METHOD_OCR];
    }
}
