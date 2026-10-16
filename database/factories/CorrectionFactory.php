<?php

namespace Database\Factories;

use App\Enums\ErrorType;
use App\Models\Correction;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Correction>
 */
class CorrectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feedback_id' => Feedback::factory(),
            'wrong_text' => fake()->words(2, true),
            'correct_text' => fake()->words(2, true),
            'error_type' => fake()->randomElement(ErrorType::cases()),
            'explanation' => fake()->sentence(),
            'start_offset' => null,
            'end_offset' => null,
            'understood_at' => null,
        ];
    }
}
