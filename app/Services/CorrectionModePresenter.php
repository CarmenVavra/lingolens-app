<?php

namespace App\Services;

use App\Enums\ErrorType;
use App\Models\Correction;
use App\Models\Feedback;
use App\Models\Scan;
use App\Support\AnnotatedText;
use Illuminate\Support\Collection;

/**
 * Prepares the split-screen correction mode: annotated paragraphs for the
 * paper, the correction list and initial filter state for Alpine.js.
 */
class CorrectionModePresenter
{
    /**
     * @return array{
     *     feedback: Feedback|null,
     *     paragraphs: list<array{heading: bool, segments: list<array{text: string, correction: Correction|null}>}>,
     *     corrections: list<array{id: int, type: string, wrong: string, correct: string, explanation: string, understood: bool, located: bool}>,
     *     legend: list<array{type: ErrorType, count: int}>,
     *     initial: array{hidden: list<string>, selected: int|null},
     *     palette: array<string, array<string, string>>,
     * }
     */
    public function present(Scan $scan, ?ErrorType $filter = null): array
    {
        $scan->loadMissing('latestFeedback.corrections');

        $feedback = $scan->latestFeedback;
        /** @var Collection<int, Correction> $corrections */
        $corrections = $feedback ? $feedback->corrections : collect();

        $counts = $corrections->countBy(fn (Correction $correction) => $correction->error_type->value);

        $legend = collect(ErrorType::cases())
            ->filter(fn (ErrorType $type) => $counts->has($type->value))
            ->map(fn (ErrorType $type) => ['type' => $type, 'count' => (int) $counts->get($type->value)])
            ->values()
            ->all();

        // A filter from the dashboard hides every other type and selects the first match.
        $hidden = [];
        $selected = null;

        if ($filter && $counts->has($filter->value)) {
            $hidden = collect($legend)
                ->map(fn (array $item) => $item['type']->value)
                ->reject(fn (string $type) => $type === $filter->value)
                ->values()
                ->all();
            $selected = $corrections->first(fn (Correction $correction) => $correction->error_type === $filter)?->id;
        }

        return [
            'feedback' => $feedback,
            'paragraphs' => AnnotatedText::paragraphs((string) $scan->raw_extracted_text, $corrections),
            'corrections' => $corrections->map(fn (Correction $correction) => [
                'id' => $correction->id,
                'type' => $correction->error_type->value,
                'wrong' => $correction->wrong_text,
                'correct' => $correction->correct_text,
                'explanation' => $correction->explanation,
                'understood' => $correction->understood_at !== null,
                'located' => $correction->isLocated(),
            ])->values()->all(),
            'legend' => $legend,
            'initial' => ['hidden' => $hidden, 'selected' => $selected],
            'palette' => ErrorType::palette(),
        ];
    }
}
