<?php

namespace Tests\Feature;

use App\Enums\ScanSource;
use App\Enums\TaskFit;
use App\Jobs\ProcessTextScan;
use App\Models\Scan;
use App\Models\User;
use App\Services\Correction\CorrectionPromptBuilder;
use App\Services\Rubric\SrdpRubric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Assessment along the SRDP-Bewertungsraster B1 (four criteria, 0–10 points).
 */
class SrdpRubricTest extends TestCase
{
    use RefreshDatabase;

    private const TASK = "You have decided to comment on this blog entry.\n- state your opinion\n- discuss effects\n- suggest alternatives\nWrite about 250 words.";

    public function test_the_required_word_count_is_read_from_the_task(): void
    {
        $this->assertSame(250, SrdpRubric::requiredWords('Give your article a title. Write about 250 words.'));
        $this->assertSame(250, SrdpRubric::requiredWords('Write 200–250 words.'));
        $this->assertSame(200, SrdpRubric::requiredWords('Write around 200 words'));
        $this->assertNull(SrdpRubric::requiredWords('Write a leaflet.'));
        $this->assertNull(SrdpRubric::requiredWords(null));
    }

    public function test_ai_scores_are_clamped_and_mapped_to_the_criteria(): void
    {
        $criteria = SrdpRubric::normalize([
            'erfuellung' => ['punkte' => 12, 'begruendung' => ' Sehr gut. '],
            'kohaerenz' => ['punkte' => '7', 'begruendung' => 'Klar.'],
            'spektrum' => ['punkte' => -3],
            'sprachrichtigkeit' => 'kaputt',
        ]);

        $this->assertSame(['score' => 10, 'reason' => 'Sehr gut.'], $criteria['task']);
        $this->assertSame(7, $criteria['coherence']['score']);
        $this->assertSame(0, $criteria['range']['score']);
        $this->assertNull($criteria['accuracy']['score']);
        $this->assertNull(SrdpRubric::normalize('kein Objekt'));
    }

    public function test_more_than_ten_percent_off_the_word_count_lowers_the_task_score_by_one_level(): void
    {
        $rubric = app(SrdpRubric::class);
        $criteria = $this->criteria(8, 7, 4, 5);

        $tooShort = $rubric->assess($criteria, Scan::factory()->make(['task_text' => self::TASK, 'word_count' => 200]), TaskFit::Fits);
        $this->assertTrue($tooShort['word_penalty']);
        $this->assertSame(7, $tooShort['criteria']['task']['score']);
        $this->assertSame(23, $tooShort['total']);
        $this->assertSame(40, $tooShort['max']);
        $this->assertSame(250, $tooShort['required_words']);

        $withinTolerance = $rubric->assess($criteria, Scan::factory()->make(['task_text' => self::TASK, 'word_count' => 270]), TaskFit::Fits);
        $this->assertFalse($withinTolerance['word_penalty']);
        $this->assertSame(8, $withinTolerance['criteria']['task']['score']);
    }

    public function test_a_missed_task_gives_zero_and_the_other_criteria_are_not_assessed(): void
    {
        $result = app(SrdpRubric::class)->assess(
            $this->criteria(4, 7, 6, 6),
            Scan::factory()->make(['task_text' => self::TASK, 'word_count' => 250]),
            TaskFit::OffTopic,
        );

        $this->assertTrue($result['off_topic']);
        $this->assertSame(0, $result['criteria']['task']['score']);
        $this->assertNull($result['criteria']['coherence']['score']);
        $this->assertNull($result['criteria']['accuracy']['score']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(10, $result['max']);
    }

    public function test_the_prompt_contains_the_raster_and_asks_for_scores(): void
    {
        $prompts = app(CorrectionPromptBuilder::class);

        $withTask = $prompts->build(Scan::factory()->make(['task_text' => self::TASK]));
        $this->assertStringContainsString('SRDP-Bewertungsraster B1', $withTask);
        $this->assertStringContainsString("'srdp_bewertung'", $withTask);
        $this->assertStringContainsString('[sprachrichtigkeit] Sprachrichtigkeit:', $withTask);
        $this->assertStringContainsString('Berücksichtige die Wortanzahl NICHT', $withTask);
        $this->assertStringContainsString('verfehlt, vergib', $withTask);

        $withoutTask = $prompts->build(Scan::factory()->make(['task_text' => null]));
        $this->assertStringContainsString("'punkte' auf null", $withoutTask);
    }

    public function test_openai_scores_are_stored_and_shown_in_correction_mode(): void
    {
        config(['services.openai.key' => 'test-openai-key']);
        Storage::fake('local');
        $text = 'I wholeheartedly agree with the decision to ban laptops in coffee shops because people need to talk.';

        Http::fake(fn (Request $request) => Http::response(['choices' => [['message' => ['content' => is_array($request['messages'][1]['content'])
            ? $text
            : json_encode([
                'title' => 'Laptop ban', 'summary' => 'Gut.', 'textsorten_check' => 'Ok.', 'matura_tipp' => 'Tipp.', 'corrections' => [],
                'aufgaben_check' => ['bewertung' => 'teilweise', 'begruendung' => 'Alternativen fehlen.', 'tipps' => []],
                'srdp_bewertung' => [
                    'erfuellung' => ['punkte' => 7, 'begruendung' => 'Zwei von drei Inhaltspunkten.'],
                    'kohaerenz' => ['punkte' => 7, 'begruendung' => 'Klar gegliedert.'],
                    'spektrum' => ['punkte' => 4, 'begruendung' => 'Viele Wiederholungen.'],
                    'sprachrichtigkeit' => ['punkte' => 5, 'begruendung' => 'Elementare Fehler.'],
                ],
            ])]]]]));

        $user = User::factory()->create();
        $scan = Scan::factory()->for($user)->create([
            'uses_sample' => false,
            'text_type' => 'Blog Comment',
            'task_text' => self::TASK,
            'file_path' => UploadedFile::fake()->image('text.jpg')->store('scans/1', 'local'),
            'source' => ScanSource::Photo,
        ]);

        ProcessTextScan::dispatchSync($scan);

        $rubric = $scan->refresh()->latestFeedback->rubric;
        $this->assertTrue($rubric['word_penalty'], 'A 17-word text is far below 250 words.');
        $this->assertSame(6, $rubric['criteria']['task']['score']);
        $this->assertSame(22, $rubric['total']);

        $this->actingAs($user)->get(route('scans.show', $scan))
            ->assertOk()
            ->assertSeeInOrder(['SRDP-Bewertungsraster B1', '22', 'von 40 Punkten'])
            ->assertSeeInOrder(['Erfüllung der Aufgabenstellung', 'Kohärenz und Kohäsion', 'Spektrum sprachlicher Mittel', 'Sprachrichtigkeit'])
            ->assertSee('Viele Wiederholungen.')
            ->assertSee('um eine Stufe herabgesetzt')
            ->assertSee('die Note vergibt deine Lehrkraft');
    }

    public function test_without_a_task_the_task_criterion_stays_open(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->for($user)->create(['uses_sample' => true, 'text_type' => 'Technical Report']);

        ProcessTextScan::dispatchSync($scan);

        $rubric = $scan->refresh()->latestFeedback->rubric;
        $this->assertNull($rubric['criteria']['task']['score']);
        $this->assertSame(18, $rubric['total']);
        $this->assertSame(30, $rubric['max']);

        $this->actingAs($user)->get(route('scans.show', $scan))
            ->assertOk()
            ->assertSee('von 30 Punkten')
            ->assertSee('Ohne Aufgabenstellung nicht bewertbar.');
    }

    public function test_the_install_command_reports_the_ai_mode(): void
    {
        $this->artisan('lingolens:install')
            ->expectsOutputToContain('KI-Modus: DEMO – OPENAI_API_KEY fehlt')
            ->assertSuccessful();

        config(['services.openai.key' => 'test-openai-key']);

        $this->artisan('lingolens:install')
            ->expectsOutputToContain('KI-Modus: OpenAI (Korrektur: gpt-4o')
            ->assertSuccessful();
    }

    /**
     * @return array<string, array{score: int|null, reason: string}>
     */
    private function criteria(int $task, int $coherence, int $range, int $accuracy): array
    {
        return [
            'task' => ['score' => $task, 'reason' => ''],
            'coherence' => ['score' => $coherence, 'reason' => ''],
            'range' => ['score' => $range, 'reason' => ''],
            'accuracy' => ['score' => $accuracy, 'reason' => ''],
        ];
    }
}
