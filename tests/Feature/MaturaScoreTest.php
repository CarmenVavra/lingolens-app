<?php

namespace Tests\Feature;

use App\Models\Correction;
use App\Models\User;
use App\Services\MaturaScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaturaScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_completed_scans_means_no_score(): void
    {
        $user = User::factory()->create();

        $this->assertSame(['score' => null, 'delta' => null], app(MaturaScoreCalculator::class)->forUser($user));
    }

    public function test_score_rises_when_corrections_are_understood(): void
    {
        $user = User::factory()->create();
        $scan = $this->completedScan($user);
        $calculator = app(MaturaScoreCalculator::class);

        $before = $calculator->forUser($user)['score'];
        Correction::query()->update(['understood_at' => now()]);
        $after = $calculator->forUser($user)['score'];

        // 11 errors on the sample text's words, all understood → +15 points.
        $density = 11 / $scan->word_count * 100;
        $expectedBase = 100 - MaturaScoreCalculator::PENALTY_PER_ERROR * $density;

        $this->assertSame((int) round($expectedBase * 0.85), $before);
        $this->assertSame((int) round($expectedBase * 0.85 + 15), $after);
    }

    public function test_delta_compares_with_the_state_one_month_ago(): void
    {
        $user = User::factory()->create();
        $old = $this->completedScan($user);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();
        $this->completedScan($user, 'Essay');

        $result = app(MaturaScoreCalculator::class)->forUser($user);

        $this->assertNotNull($result['score']);
        $this->assertNotNull($result['delta']);
    }
}
