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
            $length = mb_strlen($needle);
            $found = null;

            if ($length > 0) {
                foreach ([$cursor, 0] as $from) {
                    $found = self::findFree($text, $needle, $from, $taken, false)
                        ?? self::findFree($text, $needle, $from, $taken, true);

                    if ($found !== null) {
                        break;
                    }
                }
            }

            if ($found === null) {
                $result[] = null;

                continue;
            }

            $taken[] = [$found, $found + $length];
            $cursor = $found + $length;
            $result[] = [$found, $found + $length];
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
     */
    private static function findFree(string $haystack, string $needle, int $from, array $taken, bool $caseInsensitive): ?int
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

            $overlaps = collect($taken)->contains(
                fn (array $range) => $position < $range[1] && $position + $length > $range[0]
            );

            if (! $overlaps) {
                return $position;
            }

            $offset = $position + 1;
        }
    }
}
