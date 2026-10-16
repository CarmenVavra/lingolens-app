<?php

namespace Database\Seeders;

use App\Actions\StoreScanFeedback;
use App\Enums\ScanStatus;
use App\Enums\SchoolType;
use App\Models\Scan;
use App\Models\User;
use App\Support\DemoSamples;
use Illuminate\Database\Seeder;

/**
 * Demo data matching the design handoff (student "Lena Gruber", 5CHC, HTL).
 *
 * Test login: lena@lingolens.test / password
 */
class DatabaseSeeder extends Seeder
{
    public function run(StoreScanFeedback $storeFeedback): void
    {
        $lena = User::factory()->create([
            'name' => 'Lena Gruber',
            'email' => 'lena@lingolens.test',
            'school_type' => SchoolType::BHS,
            'school_form' => 'HTL',
            'department' => 'Chemieingenieurwesen',
            'class_name' => '5CHC',
            'grade' => 5,
        ]);

        // Older completed scans (#1–#8) give the dashboard its statistics.
        $older = [
            [56, 'Technical Report', 'Lab Report: Titration of Acetic Acid', 0.1],
            [50, 'Essay', 'Social Media and Young People', 0.1],
            [45, 'Technical Report', 'Lab Report: Recrystallisation', 0.2],
            [38, 'Technical Report', 'Lab Report: Melting Point Analysis', 0.2],
            [31, 'Executive Summary', 'Executive Summary: Wastewater Plant', 0.2],
            [24, 'Technical Report', 'Lab Report: Esterification', 0.7],
            [17, 'Essay', 'Climate Change and Industry', 0.8],
            [10, 'Technical Report', 'Lab Report: Chromatography', 0.8],
        ];

        foreach ($older as [$daysAgo, $textType, $title, $understoodRatio]) {
            $this->completedScan($lena, $storeFeedback, $textType, $title, now()->subDays($daysAgo)->setTime(10, 5), $understoodRatio);
        }

        $this->scan($lena, 'Executive Summary', 'Executive Summary: Water Analysis', ScanStatus::Failed, now()->subDays(10)->setTime(16, 40));
        $this->scan($lena, 'Formal Letter', 'Internship Application', ScanStatus::Processing, now()->subDays(7)->setTime(14, 20));
        $this->completedScan($lena, $storeFeedback, 'Essay', 'Smartphones in Schools', now()->subDays(4)->setTime(18, 30), 0.7);
        $this->completedScan($lena, $storeFeedback, 'Technical Report', 'Lab Report: Distillation', now()->setTime(8, 14), 0);
    }

    private function scan(User $user, string $textType, string $title, ScanStatus $status, mixed $createdAt): Scan
    {
        $scan = $user->scans()->create([
            'title' => $title,
            'school_type' => SchoolType::BHS,
            'department' => 'Chemieingenieurwesen',
            'text_type' => $textType,
            'status' => $status,
            'uses_sample' => true,
            'failure_reason' => $status === ScanStatus::Failed ? 'Die Handschrift-Erkennung hat zu wenig Text gefunden.' : null,
        ]);

        $scan->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $scan;
    }

    private function completedScan(User $user, StoreScanFeedback $storeFeedback, string $textType, string $title, mixed $createdAt, float $understoodRatio): void
    {
        $sample = DemoSamples::forTextType($textType);
        $scan = $this->scan($user, $textType, $title, ScanStatus::Completed, $createdAt);

        $scan->update([
            'raw_extracted_text' => $sample['text'],
            'word_count' => str_word_count($sample['text']),
            'progress_step' => Scan::TOTAL_STEPS,
        ]);

        $feedback = $storeFeedback->handle($scan, $sample);
        $feedback->forceFill(['created_at' => $createdAt])->save();

        $corrections = $feedback->corrections()->get();
        $corrections->take((int) round($corrections->count() * $understoodRatio))
            ->each(fn ($correction) => $correction->update(['understood_at' => $createdAt]));
    }
}
