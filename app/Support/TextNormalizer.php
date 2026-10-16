<?php

namespace App\Support;

/**
 * Cleans text from OCR, PDFs and documents before it is stored and corrected:
 * UTF-8, Unix line breaks, single spaces, at most one blank line between paragraphs.
 */
class TextNormalizer
{
    public static function normalize(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        $text = (string) preg_replace('/^\x{FEFF}/u', '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = (string) preg_replace('/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}]/u', '', $text);
        $text = (string) preg_replace('/[^\S\n]+/u', ' ', $text);
        $text = (string) preg_replace('/ *\n */', "\n", $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Number of letters – used to tell a real text layer from an empty scan.
     */
    public static function letterCount(string $text): int
    {
        return (int) preg_match_all('/\p{L}/u', $text);
    }
}
