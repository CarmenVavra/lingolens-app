<?php

namespace App\Services\Correction;

use App\Models\Scan;
use App\Support\DemoSamples;

/**
 * Demo mode: returns the sample feedback matching the chosen text type.
 */
class DemoTextCorrector implements TextCorrector
{
    public function correct(Scan $scan, string $text): array
    {
        $sample = DemoSamples::forTextType($scan->text_type);
        unset($sample['text']);

        return $sample;
    }
}
