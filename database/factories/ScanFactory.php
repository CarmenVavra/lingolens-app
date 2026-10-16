<?php

namespace Database\Factories;

use App\Enums\ScanStatus;
use App\Enums\SchoolType;
use App\Models\Scan;
use App\Models\User;
use App\Support\DemoSamples;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scan>
 */
class ScanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => null,
            'raw_extracted_text' => null,
            'word_count' => 0,
            'school_type' => SchoolType::BHS,
            'department' => 'Chemieingenieurwesen',
            'text_type' => 'Technical Report',
            'status' => ScanStatus::Processing,
            'progress_step' => 1,
            'uses_sample' => true,
        ];
    }

    /**
     * Completed scan with the OCR text of the matching demo sample.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $sample = DemoSamples::forTextType($attributes['text_type'] ?? 'Technical Report');

            return [
                'title' => $sample['title'],
                'raw_extracted_text' => $sample['text'],
                'word_count' => str_word_count($sample['text']),
                'status' => ScanStatus::Completed,
                'progress_step' => Scan::TOTAL_STEPS,
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ScanStatus::Failed,
            'failure_reason' => 'Die Handschrift-Erkennung hat zu wenig Text gefunden.',
        ]);
    }
}
