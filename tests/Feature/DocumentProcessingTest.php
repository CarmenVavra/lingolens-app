<?php

namespace Tests\Feature;

use App\Enums\ScanSource;
use App\Enums\ScanStatus;
use App\Jobs\ProcessTextScan;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\DocumentFixtures;
use Tests\TestCase;

/**
 * Uploaded documents: typed and scanned PDFs, Word and text files.
 */
class DocumentProcessingTest extends TestCase
{
    use RefreshDatabase;

    private const ESSAY = 'Nowadays almost every student have a smartphone. In my opinion I think this is both a chance and a problem.';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.vision_key' => 'vision-key', 'services.openai.key' => 'openai-key']);
        Storage::fake('local');
    }

    public function test_typed_pdfs_are_read_from_the_text_layer_without_ocr(): void
    {
        $this->fakeApis();
        $scan = $this->scanWithFile(ScanSource::Pdf, DocumentFixtures::pdf([
            'Nowadays almost every student have a smartphone.',
            'In my opinion I think this is both a chance and a problem.',
        ]));

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh()->load('latestFeedback.corrections');
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame('text_layer', $scan->extraction_method);
        $this->assertStringContainsString('every student have a smartphone', $scan->raw_extracted_text);
        $this->assertNotNull($scan->latestFeedback->corrections->first()->start_offset, 'Corrections are located in the PDF text.');
        Storage::disk('local')->assertMissing('scans/1/upload');

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'vision.googleapis.com'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.openai.com')
            && str_contains($request['messages'][1]['content'], 'every student have a smartphone'));
    }

    public function test_scanned_pdfs_without_text_layer_are_read_by_openai(): void
    {
        Http::fake(function (Request $request) {
            $isOcr = is_array($request['messages'][1]['content']);

            return Http::response(['choices' => [['message' => ['content' => $isOcr ? self::ESSAY : json_encode([
                'title' => 'Smartphones', 'summary' => 'Gut.', 'textsorten_check' => 'Passt.', 'matura_tipp' => 'Weiter so.',
                'corrections' => [['original' => 'student have', 'corrected' => 'student has', 'type' => 'Grammar', 'explanation' => 'Singular.']],
            ])]]]]);
        });
        $scan = $this->scanWithFile(ScanSource::Pdf, DocumentFixtures::scannedPdf());

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh();
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame('ocr', $scan->extraction_method);
        $this->assertSame(self::ESSAY, $scan->raw_extracted_text);

        Http::assertSent(fn (Request $request) => is_array($request['messages'][1]['content'])
            && $request['messages'][1]['content'][0]['type'] === 'file'
            && str_starts_with($request['messages'][1]['content'][0]['file']['file_data'], 'data:application/pdf;base64,'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'googleapis.com'));
    }

    public function test_scanned_pdfs_without_text_layer_are_read_by_google_vision(): void
    {
        config(['lingolens.ocr_provider' => 'google']);
        $this->fakeApis(visionPdfPages: ['Nowadays almost every student have a smartphone.', 'In my opinion I think this is both a chance and a problem.']);
        $scan = $this->scanWithFile(ScanSource::Pdf, DocumentFixtures::scannedPdf());

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh();
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame('ocr', $scan->extraction_method);
        $this->assertSame(self::ESSAY_PAGES, $scan->raw_extracted_text);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'vision.googleapis.com/v1/files:annotate')
            && $request['requests'][0]['inputConfig']['mimeType'] === 'application/pdf'
            && $request['requests'][0]['features'][0]['type'] === 'DOCUMENT_TEXT_DETECTION');
    }

    public function test_word_documents_keep_their_paragraphs(): void
    {
        $this->fakeApis();
        $scan = $this->scanWithFile(ScanSource::Word, DocumentFixtures::docx([
            'Smartphones in Schools',
            'Nowadays almost every student have a smartphone.',
            'In my opinion I think this is both a chance and a problem.',
        ]));

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh();
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame('document', $scan->extraction_method);
        $this->assertSame(
            "Smartphones in Schools\n\nNowadays almost every student have a smartphone.\n\nIn my opinion I think this is both a chance and a problem.",
            $scan->raw_extracted_text
        );
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'vision.googleapis.com'));
    }

    public function test_text_files_in_windows_encoding_are_converted_to_utf8(): void
    {
        $this->fakeApis();
        $scan = $this->scanWithFile(ScanSource::Text, mb_convert_encoding("Café essay\r\n\r\n".self::ESSAY, 'Windows-1252', 'UTF-8'));

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh();
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame("Café essay\n\n".self::ESSAY, $scan->raw_extracted_text);
    }

    public function test_broken_pdfs_fail_with_a_helpful_message(): void
    {
        $this->fakeApis();
        $scan = $this->scanWithFile(ScanSource::Pdf, 'not really a pdf');

        ProcessTextScan::dispatchSync($scan);

        $scan->refresh();
        $this->assertSame(ScanStatus::Failed, $scan->status);
        $this->assertSame('Die Datei ist kein gültiges PDF.', $scan->failure_reason);
        $this->assertNull($scan->file_path);
        Http::assertNothingSent();
    }

    public function test_very_long_texts_are_rejected(): void
    {
        $this->fakeApis();
        $scan = $this->scanWithFile(ScanSource::Text, str_repeat('This sentence is far too long for one Matura text. ', 400));

        ProcessTextScan::dispatchSync($scan);

        $this->assertSame(ScanStatus::Failed, $scan->refresh()->status);
        $this->assertStringContainsString('Der Text ist zu lang', $scan->failure_reason);
        Http::assertNothingSent();
    }

    public function test_the_processing_screen_shows_document_steps(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->for($user)->create([
            'source' => ScanSource::Pdf,
            'uses_sample' => false,
            'original_name' => 'Lab Report.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('scans.processing', $scan))
            ->assertOk()
            ->assertSee('PDF hochgeladen')
            ->assertSee('Text aus PDF lesen')
            ->assertSee('Lab Report.pdf')
            ->assertSee('LingoLens liest deinen Text …')
            ->assertDontSee('Handschrift erkennen');

        // Documents are never served back, only photos.
        $this->get(route('scans.preview', $scan))->assertNotFound();
    }

    private const ESSAY_PAGES = "Nowadays almost every student have a smartphone.\n\nIn my opinion I think this is both a chance and a problem.";

    private function scanWithFile(ScanSource $source, string $contents): Scan
    {
        Storage::disk('local')->put('scans/1/upload', $contents);

        return Scan::factory()->create([
            'source' => $source,
            'uses_sample' => false,
            'text_type' => 'Essay',
            'file_path' => 'scans/1/upload',
        ]);
    }

    /**
     * @param  list<string>  $visionPdfPages
     */
    private function fakeApis(array $visionPdfPages = []): void
    {
        Http::fake([
            'vision.googleapis.com/v1/files:annotate*' => Http::response(['responses' => [[
                'responses' => array_map(fn (string $text) => ['fullTextAnnotation' => ['text' => $text]], $visionPdfPages),
            ]]]),
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'title' => 'Smartphones in Schools',
                'summary' => 'Ausgewogen.',
                'textsorten_check' => 'These fehlt.',
                'matura_tipp' => 'Inhaltspunkte abhaken.',
                'corrections' => [
                    ['original' => 'have', 'corrected' => 'has', 'type' => 'Grammar', 'explanation' => 'every + Singular.'],
                ],
            ])]]]]),
        ]);
    }
}
