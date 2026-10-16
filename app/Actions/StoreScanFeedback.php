<?php

namespace App\Actions;

use App\Enums\ErrorType;
use App\Models\Feedback;
use App\Models\Scan;
use App\Support\AnnotatedText;
use Illuminate\Support\Facades\DB;

/**
 * Persists the AI result: one feedback row plus its corrections, each with the
 * character offsets of the wrong passage inside the OCR text.
 */
class StoreScanFeedback
{
    /**
     * @param  array{summary: string, textsorten_check: string, matura_tipp: string, corrections: list<array{original: string, corrected: string, type: string, explanation: string}>}  $result
     */
    public function handle(Scan $scan, array $result): Feedback
    {
        $text = (string) $scan->raw_extracted_text;
        $offsets = AnnotatedText::locate($text, array_column($result['corrections'], 'original'));

        return DB::transaction(function () use ($scan, $result, $offsets) {
            $feedback = $scan->feedbacks()->create([
                'ai_summary' => $result['summary'],
                'layout_feedback' => $result['textsorten_check'],
                'matura_tip' => $result['matura_tipp'],
            ]);

            foreach ($result['corrections'] as $index => $correction) {
                // Passages that do not occur in the text are AI hallucinations
                // (e.g. copied prompt examples) – they cannot be marked, so skip them.
                if ($offsets[$index] === null) {
                    continue;
                }

                $feedback->corrections()->create([
                    'wrong_text' => $correction['original'],
                    'correct_text' => $correction['corrected'],
                    'error_type' => ErrorType::fromAi($correction['type']),
                    'explanation' => $correction['explanation'],
                    'start_offset' => $offsets[$index][0],
                    'end_offset' => $offsets[$index][1],
                ]);
            }

            return $feedback;
        });
    }
}
