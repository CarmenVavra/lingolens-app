<?php

namespace App\Services\Ocr;

use App\Models\Scan;

/**
 * Extracts handwritten or printed text from photos and scanned PDFs (OCR).
 */
interface TextRecognizer
{
    /**
     * @param  string  $imageContents  Raw binary image data
     * @param  bool  $task  Read the (printed) task prompt instead of the student text
     */
    public function recognizeImage(Scan $scan, string $imageContents, bool $task = false): string;

    /**
     * @param  string  $pdfContents  Raw binary PDF data (scanned pages without text layer)
     * @param  bool  $task  Read the (printed) task prompt instead of the student text
     */
    public function recognizePdf(Scan $scan, string $pdfContents, bool $task = false): string;
}
