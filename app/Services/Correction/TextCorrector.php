<?php

namespace App\Services\Correction;

use App\Models\Scan;

/**
 * Corrects a student text on B2 Matura level.
 */
interface TextCorrector
{
    /**
     * @return array{title: string, summary: string, textsorten_check: string, matura_tipp: string, corrections: list<array{original: string, corrected: string, type: string, explanation: string}>, task_check: array{fit: string, feedback: string, tips: list<array{tip: string, example: string}>}|null, rubric: array<string, array{score: int|null, reason: string}>|null}
     */
    public function correct(Scan $scan, string $text): array;
}
