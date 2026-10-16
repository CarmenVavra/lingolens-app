<?php

namespace App\Support;

use App\Models\Correction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Splits the OCR text into paragraphs of plain and marked segments so the
 * correction mode can wrap every wrong passage in an interactive element.
 */
class AnnotatedText
{
    /** Paragraphs up to this length without final punctuation count as headings. */
    private const HEADING_MAX_LENGTH = 60;

    /**
     * Find character offsets for each correction: the first not yet used
     * occurrence of wrong_text, searched sequentially from the last match.
     *
     * @param  list<string>  $wrongTexts
     * @return list<array{0: int, 1: int}|null> [start, end] per input, null if not found
     */
    public static function locate(string $text, array $wrongTexts): array
    {
        $taken = [];
        $cursor = 0;
        $result = [];

        foreach ($wrongTexts as $needle) {
            $needle = trim($needle);
            $found = null;

            if ($needle !== '') {
                foreach ([$cursor, 0] as $from) {
                    $found = self::findFree($text, $needle, $from, $taken, false)
                        ?? self::findFree($text, $needle, $from, $taken, true)
                        ?? self::findFreeFuzzy($text, $needle, $from, $taken);

                    if ($found !== null) {
                        break;
                    }
                }
            }

            if ($found === null) {
                $result[] = null;

                continue;
            }

            $taken[] = $found;
            $cursor = $found[1];
            $result[] = $found;
        }

        return $result;
    }

    /**
     * Build paragraphs for the view.
     *
     * @param  Collection<int, Correction>  $corrections
     * @return list<array{heading: bool, segments: list<array{text: string, correction: Correction|null}>}>
     */
    public static function paragraphs(string $text, Collection $corrections): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $segments = self::segments($text, $corrections);

        $paragraphs = [];
        $current = [];

        foreach ($segments as $segment) {
            if ($segment['correction'] !== null) {
                // A marked passage may span a handwriting line break.
                $segment['text'] = (string) preg_replace('/[ \t]*\n[ \t]*/', ' ', $segment['text']);
                $current[] = $segment;

                continue;
            }

            $chunks = preg_split('/\n\s*\n/', $segment['text']) ?: [];

            foreach ($chunks as $index => $chunk) {
                if ($index > 0) {
                    $paragraphs[] = $current;
                    $current = [];
                }

                // Single line breaks from the handwriting become spaces.
                $chunk = (string) preg_replace('/[ \t]*\n[ \t]*/', ' ', $chunk);

                if ($chunk !== '') {
                    $current[] = ['text' => $chunk, 'correction' => null];
                }
            }
        }

        $paragraphs[] = $current;

        return collect($paragraphs)
            ->map(fn (array $segments) => self::trimParagraph($segments))
            ->filter(fn (array $segments) => $segments !== [])
            ->map(fn (array $segments) => ['heading' => self::isHeading($segments), 'segments' => $segments])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Correction>  $corrections
     * @return list<array{text: string, correction: Correction|null}>
     */
    private static function segments(string $text, Collection $corrections): array
    {
        $located = $corrections
            ->filter(fn (Correction $correction) => $correction->isLocated())
            ->sortBy('start_offset')
            ->values();

        $segments = [];
        $position = 0;
        $length = mb_strlen($text);

        foreach ($located as $correction) {
            $start = (int) $correction->start_offset;
            $end = min((int) $correction->end_offset, $length);

            // Skip overlapping or out-of-range offsets.
            if ($start < $position || $start >= $end) {
                continue;
            }

            if ($start > $position) {
                $segments[] = ['text' => mb_substr($text, $position, $start - $position), 'correction' => null];
            }

            $segments[] = ['text' => mb_substr($text, $start, $end - $start), 'correction' => $correction];
            $position = $end;
        }

        if ($position < $length) {
            $segments[] = ['text' => mb_substr($text, $position), 'correction' => null];
        }

        return $segments;
    }

    /**
     * @param  list<array{text: string, correction: Correction|null}>  $segments
     * @return list<array{text: string, correction: Correction|null}>
     */
    private static function trimParagraph(array $segments): array
    {
        if ($segments === []) {
            return [];
        }

        $last = count($segments) - 1;

        if ($segments[0]['correction'] === null) {
            $segments[0]['text'] = ltrim($segments[0]['text']);
        }

        if ($segments[$last]['correction'] === null) {
            $segments[$last]['text'] = rtrim($segments[$last]['text']);
        }

        return array_values(array_filter(
            $segments,
            fn (array $segment) => $segment['correction'] !== null || $segment['text'] !== ''
        ));
    }

    /**
     * @param  list<array{text: string, correction: Correction|null}>  $segments
     */
    private static function isHeading(array $segments): bool
    {
        if (count($segments) !== 1 || $segments[0]['correction'] !== null) {
            return false;
        }

        $text = $segments[0]['text'];

        return mb_strlen($text) <= self::HEADING_MAX_LENGTH && ! Str::endsWith($text, ['.', '!', '?', ':', ',', ';', '"', '“']);
    }

    /**
     * @param  list<array{0: int, 1: int}>  $taken
     * @return array{0: int, 1: int}|null
     */
    private static function findFree(string $haystack, string $needle, int $from, array $taken, bool $caseInsensitive): ?array
    {
        $length = mb_strlen($needle);
        $offset = $from;

        while (true) {
            $position = $caseInsensitive
                ? mb_stripos($haystack, $needle, $offset)
                : mb_strpos($haystack, $needle, $offset);

            if ($position === false) {
                return null;
            }

            if (! self::overlaps($position, $position + $length, $taken)) {
                return [$position, $position + $length];
            }

            $offset = $position + 1;
        }
    }

    /**
     * Fallback for passages the OCR split over two lines (handwriting line
     * breaks) or that the AI quotes with different spacing or quote marks:
     * any whitespace in the passage may be spaces or a single line break (never
     * a blank line); straight and typographic quotes count as the same.
     *
     * @param  list<array{0: int, 1: int}>  $taken
     * @return array{0: int, 1: int}|null
     */
    private static function findFreeFuzzy(string $haystack, string $needle, int $from, array $taken): ?array
    {
        $words = preg_split('/\s+/u', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return null;
        }

        $single = "['\u{2019}\u{2018}]";
        $double = "[\"\u{201C}\u{201D}\u{201E}]";
        $quotes = [
            "'" => $single, "\u{2019}" => $single, "\u{2018}" => $single,
            '"' => $double, "\u{201C}" => $double, "\u{201D}" => $double, "\u{201E}" => $double,
        ];
        $words = array_map(fn (string $word) => strtr(preg_quote($word, '/'), $quotes), $words);
        $pattern = '/'.implode('(?:[ \t]*\r?\n[ \t]*|[ \t]+)', $words).'/iu';
        $byteOffset = strlen(mb_substr($haystack, 0, $from));

        while (preg_match($pattern, $haystack, $match, PREG_OFFSET_CAPTURE, $byteOffset) === 1) {
            [$matched, $bytePosition] = $match[0];
            $start = mb_strlen(substr($haystack, 0, $bytePosition));
            $end = $start + mb_strlen($matched);

            if (! self::overlaps($start, $end, $taken)) {
                return [$start, $end];
            }

            $byteOffset = $bytePosition + strlen(mb_substr($matched, 0, 1));
        }

        return null;
    }

    /**
     * @param  list<array{0: int, 1: int}>  $taken
     */
    private static function overlaps(int $start, int $end, array $taken): bool
    {
        return collect($taken)->contains(fn (array $range) => $start < $range[1] && $end > $range[0]);
    }
}
