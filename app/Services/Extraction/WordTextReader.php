<?php

namespace App\Services\Extraction;

use App\Exceptions\ScanProcessingException;
use DOMDocument;
use DOMElement;
use DOMXPath;
use ZipArchive;

/**
 * Reads the text of a Word document (.docx): every Word paragraph becomes a
 * paragraph separated by a blank line, so headings stay recognisable.
 */
class WordTextReader
{
    private const WORD_NAMESPACE = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function read(string $contents): string
    {
        $xml = $this->documentXml($contents);

        $dom = new DOMDocument;
        // LIBXML_NONET: never load external resources (XXE protection).
        if (! @$dom->loadXML($xml, LIBXML_NONET)) {
            throw new ScanProcessingException('Die Word-Datei konnte nicht gelesen werden.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NAMESPACE);

        $paragraphs = [];

        foreach ($xpath->query('//w:body//w:p') ?: [] as $paragraph) {
            $text = '';

            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) ?: [] as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $text .= match ($node->localName) {
                    'tab' => ' ',
                    'br' => "\n",
                    default => $node->textContent,
                };
            }

            $paragraphs[] = $text;
        }

        return implode("\n\n", $paragraphs);
    }

    private function documentXml(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lingolens-docx');

        try {
            file_put_contents($path, $contents);

            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::RDONLY) !== true) {
                throw new ScanProcessingException('Die Word-Datei ist beschädigt. Bitte speichere sie als .docx oder PDF.');
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                throw new ScanProcessingException('Die Word-Datei enthält keinen lesbaren Text.');
            }

            return $xml;
        } finally {
            @unlink($path);
        }
    }
}
