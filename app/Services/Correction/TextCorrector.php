<?php

namespace App\Services\Correction;

use App\Models\Scan;

/**
 * Corrects a student text on B2 Matura level.
 */
interface TextCorrector
{
    /**
     * @return array{title: string, summary: string, textsorten_check: string, matura_tipp: string, corrections: list<array{original: string, corrected: string, type: string, explanation: string}>}
     */
    public function correct(Scan $scan, string $text): array;
}
