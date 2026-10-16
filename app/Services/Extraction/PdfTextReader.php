<?php

namespace App\Services\Extraction;

use App\Exceptions\ScanProcessingException;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Reads the text layer of a PDF (texts typed on a laptop). Scanned or
 * photographed pages have no text layer and return an empty string.
 */
class PdfTextReader
{
    public function read(string $contents): string
    {
        if (! str_contains(substr($contents, 0, 1024), '%PDF-')) {
            throw new ScanProcessingException('Die Datei ist kein gültiges PDF.');
        }

        try {
            $document = (new Parser)->parseContent($contents);

            return collect($document->getPages())
                ->map(fn (Page $page) => $page->getText())
                ->implode("\n\n");
        } catch (Throwable) {
            throw new ScanProcessingException('Das PDF konnte nicht geöffnet werden. Ist es passwortgeschützt oder beschädigt?');
        }
    }
}
