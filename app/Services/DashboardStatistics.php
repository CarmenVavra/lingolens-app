<?php

namespace App\Services;

use App\Enums\ErrorType;
use App\Models\Correction;
use App\Models\Scan;
use App\Models\User;
use App\Support\LingoLens;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Collects everything the dashboard shows: error distribution, score,
 * weekly focus and the latest scans.
 */
class DashboardStatistics
{
    public function __construct(private readonly MaturaScoreCalculator $scores) {}

    /**
     * @return array{
     *     stats: list<array{type: ErrorType, count: int, rank: int, percent: int, url: string|null}>,
     *     totalCorrections: int,
     *     completedScans: int,
     *     score: int|null,
     *     scoreDelta: int|null,
     *     scoreCells: list<string>,
     *     lever: ErrorType|null,
     *     focus: array{type: ErrorType, title: string, text: string, button: string, url: string|null}|null,
     *     recentScans: Collection<int, Scan>,
     *     daysUntilMatura: int,
     * }
     */
    public function forUser(User $user): array
    {
        $errorStats = $this->errorStats($user);
        $total = (int) $errorStats->sum('total');
        $practiceScans = $this->latestScanPerErrorType($user);

        $stats = $errorStats->values()->map(fn (Correction $row, int $index) => [
            'type' => $row->error_type,
            'count' => (int) $row->getAttribute('total'),
            'rank' => $index + 1,
            'percent' => $total > 0 ? (int) round($row->getAttribute('total') / $total * 100) : 0,
            'url' => $this->practiceUrl($practiceScans->get($row->error_type->value), $row->error_type),
        ])->all();

        $score = $this->scores->forUser($user);
        $topType = $stats[0]['type'] ?? null;

        return [
            'stats' => $stats,
            'totalCorrections' => $total,
            'completedScans' => $user->scans()->completed()->count(),
            'score' => $score['score'],
            'scoreDelta' => $score['delta'],
            'scoreCells' => $this->scoreCells($score['score']),
            'lever' => $topType,
            'focus' => $topType ? $this->focus($user, $topType, $stats[0]['count'], $total, $practiceScans) : null,
            'recentScans' => $user->scans()->withCount('corrections')->latest()->latest('id')->take(3)->get(),
            'daysUntilMatura' => LingoLens::daysUntilMatura(),
        ];
    }

    /**
     * Error distribution per type, sorted by count (see blueprint, DashboardController).
     *
     * @return Collection<int, Correction>
     */
    public function errorStats(User $user): Collection
    {
        return Correction::whereHas('feedback.scan', function (Builder $query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->select('error_type', DB::raw('count(*) as total'))
            ->groupBy('error_type')
            ->orderByDesc('total')
            ->orderBy('error_type')
            ->get();
    }

    /**
     * 20 cells of the score bar: filled (raspberry), next two (peach), rest (faint).
     *
     * @return list<string>
     */
    private function scoreCells(?int $score): array
    {
        $filled = (int) round(($score ?? 0) / 5);

        return array_map(
            fn (int $index) => $index < $filled ? 'filled' : ($index < $filled + 2 && $score !== null ? 'next' : 'empty'),
            range(0, 19)
        );
    }

    /**
     * Id of the most recent completed scan that contains each error type.
     *
     * @return Collection<string, int>
     */
    private function latestScanPerErrorType(User $user): Collection
    {
        return DB::table('corrections')
            ->join('feedbacks', 'feedbacks.id', '=', 'corrections.feedback_id')
            ->join('scans', 'scans.id', '=', 'feedbacks.scan_id')
            ->where('scans.user_id', $user->id)
            ->where('scans.status', 'completed')
            ->groupBy('corrections.error_type')
            ->select('corrections.error_type', DB::raw('max(scans.id) as scan_id'))
            ->pluck('scan_id', 'error_type')
            ->map(fn ($id) => (int) $id);
    }

    private function practiceUrl(?int $scanId, ErrorType $type): ?string
    {
        return $scanId ? route('scans.show', ['scan' => $scanId, 'type' => $type->value]) : null;
    }

    /**
     * @param  Collection<string, int>  $practiceScans
     * @return array{type: ErrorType, title: string, text: string, button: string, url: string|null}
     */
    private function focus(User $user, ErrorType $type, int $count, int $total, Collection $practiceScans): array
    {
        $example = Correction::whereHas('feedback.scan', fn (Builder $query) => $query->where('user_id', $user->id))
            ->where('error_type', $type)
            ->latest('id')
            ->first();

        $copy = config('lingolens.focus.'.$type->value);

        return [
            'type' => $type,
            'title' => $copy['title'],
            'text' => strtr($copy['text'], [
                ':count' => (string) $count,
                ':total' => (string) $total,
                ':wrong' => $example ? $example->wrong_text : '…',
                ':correct' => $example ? $example->correct_text : '…',
            ]),
            'button' => $copy['button'],
            'url' => $this->practiceUrl($practiceScans->get($type->value), $type),
        ];
    }
}
