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
     */
    public function recognizeImage(Scan $scan, string $imageContents): string;

    /**
     * @param  string  $pdfContents  Raw binary PDF data (scanned pages without text layer)
     */
    public function recognizePdf(Scan $scan, string $pdfContents): string;
}
