<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scan_id' => Scan::factory()->completed(),
            'ai_summary' => fake()->sentence(12),
            'layout_feedback' => fake()->sentence(12),
            'matura_tip' => fake()->sentence(12),
        ];
    }
}
