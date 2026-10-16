<?php

namespace Tests\Support;

use RuntimeException;
use ZipArchive;

/**
 * Builds small but valid PDF and DOCX files for upload and extraction tests.
 */
class DocumentFixtures
{
    /**
     * A one-page PDF with a real text layer (one line per entry).
     *
     * @param  list<string>  $lines
     */
    public static function pdf(array $lines): string
    {
        $content = "BT\n/F1 12 Tf\n14 TL\n72 720 Td\n";
        foreach ($lines as $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "({$escaped}) Tj T*\n";
        }
        $content .= 'ET';

        return self::buildPdf($content);
    }

    /**
     * A one-page PDF without any text (like a scanned page without OCR layer).
     */
    public static function scannedPdf(): string
    {
        return self::buildPdf("0.5 g\n72 600 200 100 re f");
    }

    /**
     * A minimal Word document (.docx) with one paragraph per entry.
     *
     * @param  list<string>  $paragraphs
     */
    public static function docx(array $paragraphs): string
    {
        $body = collect($paragraphs)
            ->map(fn (string $text) => '<w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($text, ENT_XML1).'</w:t></w:r></w:p>')
            ->implode('');

        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create docx fixture.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$body.'</w:body></w:document>');
        $zip->close();

        $contents = (string) file_get_contents($path);
        unlink($path);

        return $contents;
    }

    private static function buildPdf(string $stream): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
