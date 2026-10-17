<?php

namespace Tests\Feature;

use App\Enums\ErrorType;
use App\Enums\ScanStatus;
use App\Jobs\ProcessTextScan;
use App\Models\Scan;
use App\Models\User;
use App\Support\LingoLens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessTextScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_mode_completes_the_scan_with_sample_feedback(): void
    {
        $scan = Scan::factory()->create(['uses_sample' => true, 'text_type' => 'Technical Report']);

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh()->load('latestFeedback.corrections');
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame(Scan::TOTAL_STEPS, $scan->progress_step);
        $this->assertSame('Lab Report: Distillation of an Ethanol–Water Mixture', $scan->title);
        $this->assertGreaterThan(50, $scan->word_count);
        $this->assertCount(11, $scan->latestFeedback->corrections);

        $first = $scan->latestFeedback->corrections->first();
        $this->assertSame('we describes', $first->wrong_text);
        $this->assertSame(
            'we describes',
            mb_substr($scan->raw_extracted_text, $first->start_offset, $first->end_offset - $first->start_offset)
        );
    }

    public function test_photos_are_transcribed_and_corrected_by_openai(): void
    {
        config(['services.openai.key' => 'test-openai-key', 'services.google.vision_key' => null]);
        Storage::fake('local');

        $handwriting = "Dear Agony Aunt\n\nI have a proplem with my parents!\nI have two younger brothers.";
        Http::fake(function (Request $request) use ($handwriting) {
            $isOcr = is_array($request['messages'][1]['content']);

            return Http::response(['choices' => [['message' => ['content' => $isOcr
                ? "```\n{$handwriting}\n```"
                : json_encode([
                    'title' => 'Letter to an Agony Aunt',
                    'summary' => 'Gut.',
                    'textsorten_check' => 'Anrede passt.',
                    'matura_tipp' => 'Achte auf Rechtschreibung.',
                    'corrections' => [['original' => 'proplem', 'corrected' => 'problem', 'type' => 'Vocabulary', 'explanation' => 'Schreibweise.']],
                ])]]]]);
        });

        $path = UploadedFile::fake()->image('letter.jpg')->store('scans/1', 'local');
        $scan = Scan::factory()->create(['uses_sample' => false, 'file_path' => $path, 'text_type' => 'Email']);

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh()->load('latestFeedback.corrections');
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame('ocr', $scan->extraction_method);
        $this->assertSame($handwriting, $scan->raw_extracted_text, 'Code fences around the transcription are removed.');
        $this->assertSame('proplem', $scan->latestFeedback->corrections->first()->wrong_text);
        $this->assertNotNull($scan->latestFeedback->corrections->first()->start_offset);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => is_array($request['messages'][1]['content'])
            && $request['model'] === 'gpt-4o'
            && $request['temperature'] === 0
            && str_starts_with($request['messages'][1]['content'][0]['image_url']['url'], 'data:image/jpeg;base64,')
            && str_contains($request['messages'][0]['content'], 'Korrigiere NICHTS'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'googleapis.com'));
    }

    public function test_black_bars_for_crossed_out_words_are_removed(): void
    {
        config(['services.openai.key' => 'k']);
        Storage::fake('local');
        Http::fake(function (Request $request) {
            return Http::response(['choices' => [['message' => ['content' => is_array($request['messages'][1]['content'])
                ? "I am sitting in the garden.\nHabe you ever see a \u{2588}\u{2588}\u{2588}\u{2588} bird like that?"
                : json_encode(['title' => 'Garden', 'summary' => '', 'textsorten_check' => '', 'matura_tipp' => '', 'corrections' => []]),
            ]]]]);
        });

        $path = UploadedFile::fake()->image('garden.jpg')->store('scans/1', 'local');
        $scan = Scan::factory()->create(['uses_sample' => false, 'file_path' => $path]);

        ProcessTextScan::dispatchSync($scan);

        $this->assertSame("I am sitting in the garden.\nHabe you ever see a bird like that?", $scan->refresh()->raw_extracted_text);
    }

    public function test_the_job_does_not_limit_the_runtime_of_the_queue_worker(): void
    {
        // A time limit set inside the job would stay active for the whole worker
        // process and kill it minutes later while it waits for the next scan.
        $limitBefore = ini_get('max_execution_time');

        ProcessTextScan::dispatchSync(Scan::factory()->create(['uses_sample' => true]));

        $this->assertSame($limitBefore, ini_get('max_execution_time'));
    }

    public function test_openai_mode_does_not_need_a_google_key(): void
    {
        config(['services.openai.key' => 'k', 'services.google.vision_key' => null, 'lingolens.demo_mode' => null]);
        $this->assertFalse(LingoLens::demoMode());

        config(['lingolens.ocr_provider' => 'google']);
        $this->assertTrue(LingoLens::demoMode(), 'Google OCR needs its own key.');
    }

    public function test_real_mode_calls_google_vision_and_openai(): void
    {
        config([
            'lingolens.ocr_provider' => 'google',
            'services.google.vision_key' => 'test-vision-key',
            'services.openai.key' => 'test-openai-key',
        ]);
        Storage::fake('local');

        $text = 'In this report we describes how we make an experiment. The chemical reaction makes heat and we used a distilate.';
        Http::fake([
            'vision.googleapis.com/*' => Http::response(['responses' => [['fullTextAnnotation' => ['text' => $text]]]]),
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'title' => 'Distillation Report',
                'summary' => 'Gut gegliedert.',
                'textsorten_check' => 'Conclusion fehlt.',
                'matura_tipp' => 'Lerne Laborverben.',
                'corrections' => [
                    ['original' => 'we describes', 'corrected' => 'we describe', 'type' => 'grammar', 'explanation' => 'Kein -s nach we.'],
                    ['original' => 'distilate', 'corrected' => 'fractional distillation', 'type' => 'Technical Term', 'explanation' => 'Fachbegriff.'],
                    ['original' => 'not in the text', 'corrected' => 'x', 'type' => 'Style', 'explanation' => 'Nicht gefunden.'],
                ],
            ])]]]]),
        ]);

        $path = UploadedFile::fake()->image('scan.jpg')->store('scans/1', 'local');
        $scan = Scan::factory()->create(['uses_sample' => false, 'file_path' => $path]);

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh()->load('latestFeedback.corrections');
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame('Distillation Report', $scan->title);
        $this->assertSame('Lerne Laborverben.', $scan->latestFeedback->matura_tip);
        $this->assertSame(ErrorType::Grammar, $scan->latestFeedback->corrections[0]->error_type);
        $this->assertCount(2, $scan->latestFeedback->corrections, 'Passages that are not in the text are dropped.');
        $this->assertNotContains('not in the text', $scan->latestFeedback->corrections->pluck('wrong_text'));

        // Photos are deleted after processing.
        Storage::disk('local')->assertMissing($path);
        $this->assertNull($scan->file_path);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'vision.googleapis.com')
            && str_contains($request->url(), 'key=test-vision-key'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.openai.com')
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && $request['model'] === 'gpt-4o'
            && str_contains($request['messages'][0]['content'], 'Chemieingenieurwesen')
            && str_contains($request['messages'][0]['content'], 'vacuum filtration')
            && str_contains($request['messages'][0]['content'], 'erfinde keine Fehler'));
    }

    public function test_unreadable_handwriting_marks_the_scan_as_failed(): void
    {
        config(['lingolens.ocr_provider' => 'google', 'services.google.vision_key' => 'k', 'services.openai.key' => 'k']);
        Storage::fake('local');
        Http::fake([
            'vision.googleapis.com/*' => Http::response(['responses' => [['fullTextAnnotation' => ['text' => 'abc']]]]),
        ]);

        $path = UploadedFile::fake()->image('blurry.jpg')->store('scans/1', 'local');
        $scan = Scan::factory()->create(['uses_sample' => false, 'file_path' => $path]);

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh();
        $this->assertSame(ScanStatus::Failed, $scan->status);
        $this->assertSame('Die Handschrift-Erkennung hat zu wenig Text gefunden.', $scan->failure_reason);
        Storage::disk('local')->assertMissing($path);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'openai'));
    }

    public function test_api_errors_mark_the_scan_as_failed(): void
    {
        config(['services.google.vision_key' => 'k', 'services.openai.key' => 'k']);
        Storage::fake('local');
        Http::fake(['*' => Http::response('Server error', 500)]);

        $path = UploadedFile::fake()->image('scan.jpg')->store('scans/1', 'local');
        $scan = Scan::factory()->create(['uses_sample' => false, 'file_path' => $path]);

        ProcessTextScan::dispatchSync($scan);

        $this->assertSame(ScanStatus::Failed, $scan->refresh()->status);
        $this->assertSame('Die Analyse ist fehlgeschlagen.', $scan->failure_reason);
    }

    public function test_full_flow_from_upload_to_correction_mode(): void
    {
        $user = User::factory()->create();

        // QUEUE_CONNECTION=sync in tests: the job runs right away (demo mode).
        $this->actingAs($user)->post(route('scans.store'), [
            'school_type' => 'BHS',
            'department' => 'Chemieingenieurwesen',
            'text_type' => 'Technical Report',
            'use_sample' => '1',
        ]);

        $scan = Scan::sole();

        $this->getJson(route('scans.status', $scan))
            ->assertOk()
            ->assertJson([
                'status' => 'completed',
                'progress_step' => 4,
                'percent' => 100,
                'redirect_url' => route('scans.show', $scan),
            ]);

        $this->get(route('scans.processing', $scan))->assertRedirect(route('scans.show', $scan));
        $this->get(route('scans.show', $scan))->assertOk()->assertSee('Korrektur-Modus');
    }
}
