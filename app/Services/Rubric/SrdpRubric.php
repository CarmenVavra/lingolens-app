<?php

namespace App\Services\Rubric;

use App\Enums\TaskFit;
use App\Models\Scan;

/**
 * Assessment along the SRDP criteria, always at level B2 (config/srdp.php):
 * four criteria with 0–10 points each, plus an estimated Austrian grade.
 *
 * The AI suggests a score per criterion; the fixed rules of the raster are
 * applied here, so they never depend on the model:
 * - task missed → "Erfüllung der Aufgabenstellung" 0, other criteria not assessed
 * - word count more than 10 % off → "Erfüllung der Aufgabenstellung" one level lower
 *
 * @phpstan-type Criterion array{score: int|null, reason: string}
 * @phpstan-type Assessment array{criteria: array<string, Criterion>, total: int|null, max: int, word_count: int, required_words: int|null, word_penalty: bool, off_topic: bool, level: string, percent: int|null, grade: int|null}
 */
class SrdpRubric
{
    /** Keys the AI answers with, mapped to our criterion keys. */
    public const AI_KEYS = [
        'erfuellung' => 'task',
        'kohaerenz' => 'coherence',
        'spektrum' => 'range',
        'sprachrichtigkeit' => 'accuracy',
    ];

    /**
     * Instructions for the correction prompt.
     */
    public function promptSection(Scan $scan): string
    {
        $level = config('srdp.level');
        $section = " Bewerte den Text zusätzlich nach den SRDP-Kriterien (je Kriterium 0–10 Punkte), IMMER auf Niveau {$level}"
            .' (Matura) – auch wenn in der Aufgabenstellung oder anderswo ein anderes Niveau (z.B. B1) genannt ist.'
            ." Miss Wortschatz, Strukturen und Richtigkeit an den Erwartungen für {$level}."
            .' Beschreibungen gibt es für die geraden Stufen; die ungeraden Stufen (9, 7, 5, 3, 1) liegen dazwischen'
            .' und werden vergeben, wenn ein Text Merkmale beider Nachbarstufen zeigt.';

        foreach (config('srdp.criteria') as $key => $criterion) {
            $aiKey = array_search($key, self::AI_KEYS, true);
            $section .= "\n[{$aiKey}] {$criterion['label']}:";

            foreach ($criterion['bands'] as $points => $description) {
                $section .= " {$points} = {$description}";
            }
        }

        $section .= "\nBerücksichtige die Wortanzahl NICHT – die Abwertung bei Über- oder Unterschreitung rechnet LingoLens selbst.";

        if ($scan->task_text === null) {
            $section .= " Es liegt keine Aufgabenstellung vor: Setze bei 'erfuellung' 'punkte' auf null und begründe das kurz.";
        } else {
            $section .= " Ist die Aufgabenstellung verfehlt, vergib bei 'erfuellung' 0 Punkte.";
        }

        return $section;
    }

    /**
     * JSON key description for the prompt.
     */
    public function promptKey(): string
    {
        return ", 'srdp_bewertung' (Objekt mit den Keys 'erfuellung', 'kohaerenz', 'spektrum', 'sprachrichtigkeit';"
            ." jeweils ein Objekt mit 'punkte' (ganze Zahl 0–10) und 'begruendung' (1–2 Sätze auf Deutsch, die sich auf"
            .' die Beschreibung der Stufe beziehen))';
    }

    /**
     * Turn the raw AI answer into criterion scores (still without the fixed rules).
     *
     * @return array<string, Criterion>|null
     */
    public static function normalize(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $criteria = [];

        foreach (self::AI_KEYS as $aiKey => $key) {
            $item = is_array($raw[$aiKey] ?? null) ? $raw[$aiKey] : [];
            $points = $item['punkte'] ?? null;

            $criteria[$key] = [
                'score' => is_numeric($points) ? max(0, min((int) config('srdp.max_per_criterion'), (int) round((float) $points))) : null,
                'reason' => mb_substr(trim((string) ($item['begruendung'] ?? '')), 0, 600),
            ];
        }

        return $criteria;
    }

    /**
     * Apply the fixed rules of the raster and add the totals.
     *
     * @param  array<string, Criterion>  $criteria
     * @return Assessment
     */
    public function assess(array $criteria, Scan $scan, ?TaskFit $fit): array
    {
        $criteria += array_fill_keys(array_keys(config('srdp.criteria')), ['score' => null, 'reason' => '']);
        $requiredWords = self::requiredWords($scan->task_text);
        $offTopic = $fit === TaskFit::OffTopic;
        $wordPenalty = false;

        if ($offTopic) {
            $criteria['task'] = ['score' => 0, 'reason' => 'Aufgabenstellung verfehlt – laut Raster Stufe 0, die anderen Kriterien werden nicht bewertet.'];

            foreach (['coherence', 'range', 'accuracy'] as $key) {
                $criteria[$key] = ['score' => null, 'reason' => 'Wird bei verfehlter Aufgabenstellung nicht bewertet.'];
            }
        } elseif ($requiredWords !== null && $criteria['task']['score'] !== null && $this->outsideTolerance($scan->word_count, $requiredWords)) {
            $wordPenalty = true;
            $criteria['task']['score'] = max(0, $criteria['task']['score'] - 1);
        }

        $scores = array_filter(array_column($criteria, 'score'), fn (?int $score) => $score !== null);
        $total = $scores === [] ? null : array_sum($scores);
        $max = count($scores) * (int) config('srdp.max_per_criterion');
        $complete = count($scores) === count(config('srdp.criteria'));
        $percent = $complete && $max > 0 ? (int) round($total / $max * 100) : null;

        return [
            'criteria' => $criteria,
            'total' => $total,
            'max' => $max,
            'word_count' => $scan->word_count,
            'required_words' => $requiredWords,
            'word_penalty' => $wordPenalty,
            'off_topic' => $offTopic,
            'level' => (string) config('srdp.level'),
            'percent' => $percent,
            // Only with all four criteria (i.e. with a task); a missed task is always a 5.
            'grade' => $offTopic ? 5 : ($percent === null ? null : self::grade($percent)),
        ];
    }

    /**
     * Austrian grade (1 = Sehr gut … 5 = Nicht genügend) for a share of points.
     */
    public static function grade(int $percent): int
    {
        $grades = config('srdp.grades');
        krsort($grades);

        foreach ($grades as $minimum => $grade) {
            if ($percent >= $minimum) {
                return (int) $grade;
            }
        }

        return 5;
    }

    public static function gradeLabel(int $grade): string
    {
        return (string) (config('srdp.grade_labels')[$grade] ?? '');
    }

    /**
     * Required word count from the task, e.g. "Write about 250 words." → 250.
     */
    public static function requiredWords(?string $task): ?int
    {
        if ($task === null || ! preg_match('/(\d{2,4})\s*(?:-|–)?\s*words?\b/i', $task, $match)) {
            return null;
        }

        $words = (int) $match[1];

        return $words >= 30 && $words <= 2000 ? $words : null;
    }

    private function outsideTolerance(int $words, int $required): bool
    {
        return abs($words - $required) > $required * (float) config('srdp.word_count_tolerance');
    }
}
