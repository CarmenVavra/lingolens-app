<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Matura-Ready-Score (0–100).
 *
 * Based on the error density (corrections per 100 words) of the last five
 * completed scans: each error per 100 words costs 2.5 points. 85 % of the
 * score come from that density, 15 % from how many corrections the student
 * marked as "verstanden". B2-safe from 80.
 */
class MaturaScoreCalculator
{
    public const SAMPLE_SIZE = 5;

    public const PENALTY_PER_ERROR = 2.5;

    public const SAFE_THRESHOLD = 80;

    /**
     * @return array{score: int|null, delta: int|null}
     */
    public function forUser(User $user): array
    {
        $current = $this->score($this->recentScans($user));
        $previous = $this->score($this->recentScans($user, now()->subMonth()));

        return [
            'score' => $current,
            'delta' => $current !== null && $previous !== null ? $current - $previous : null,
        ];
    }

    /**
     * @param  Collection<int, Scan>  $scans
     */
    public function score(Collection $scans): ?int
    {
        $words = (int) $scans->sum('word_count');

        if ($scans->isEmpty() || $words === 0) {
            return null;
        }

        $corrections = (int) $scans->sum('corrections_count');
        $understood = (int) $scans->sum('understood_count');

        $density = $corrections / $words * 100;
        $base = max(0, min(100, 100 - self::PENALTY_PER_ERROR * $density));
        $understoodRatio = $corrections > 0 ? $understood / $corrections : 1;

        return (int) round($base * 0.85 + $understoodRatio * 15);
    }

    /**
     * @return Collection<int, Scan>
     */
    private function recentScans(User $user, ?Carbon $before = null): Collection
    {
        return $user->scans()
            ->completed()
            ->where('word_count', '>', 0)
            ->when($before, fn ($query) => $query->where('created_at', '<', $before))
            ->withCount([
                'corrections',
                'corrections as understood_count' => fn ($query) => $query->whereNotNull('understood_at'),
            ])
            ->latest()
            ->latest('id')
            ->take(self::SAMPLE_SIZE)
            ->get();
    }
}
