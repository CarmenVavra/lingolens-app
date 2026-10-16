<?php

namespace Tests\Feature;

use App\Enums\ScanSource;
use App\Enums\ScanStatus;
use App\Jobs\ProcessTextScan;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DocumentFixtures;
use Tests\TestCase;

class ScanUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_form_is_prefilled_with_the_school_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('scans.create'));

        $response
            ->assertStatus(200)
            ->assertSee('Was hast du geschrieben?')
            ->assertSee('Fachbegriff-Check Chemie aktiv.')
            ->assertSee('Technical Report')
            ->assertSee('name="_token"', false);

        $this->assertMatchesRegularExpression('/value="BHS"\s+checked/', $response->getContent());
    }

    public function test_a_photo_creates_a_processing_scan_and_dispatches_the_job(): void
    {
        Queue::fake();
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('scans.store'), [
            'school_type' => 'BHS',
            'department' => 'Chemieingenieurwesen',
            'text_type' => 'Technical Report',
            'file' => UploadedFile::fake()->image('lab-report.jpg', 800, 1000),
        ]);

        $scan = Scan::sole();
        $response->assertRedirect(route('scans.processing', $scan));

        $this->assertSame(ScanStatus::Processing, $scan->status);
        $this->assertSame($user->id, $scan->user_id);
        $this->assertFalse($scan->uses_sample);
        $this->assertSame(ScanSource::Photo, $scan->source);
        $this->assertSame('lab-report.jpg', $scan->original_name);
        Storage::disk('local')->assertExists($scan->file_path);
        Queue::assertPushed(ProcessTextScan::class, fn (ProcessTextScan $job) => $job->scan->is($scan));
    }

    public function test_the_sample_photo_can_be_used_instead_of_an_upload(): void
    {
        Queue::fake();
        $user = User::factory()->ahs()->create();

        $this->actingAs($user)->post(route('scans.store'), [
            'school_type' => 'AHS',
            'department' => 'Chemieingenieurwesen',
            'text_type' => 'Essay',
            'use_sample' => '1',
        ])->assertRedirect();

        $scan = Scan::sole();
        $this->assertTrue($scan->uses_sample);
        $this->assertNull($scan->file_path);
        $this->assertNull($scan->department, 'AHS scans never keep a department.');
        Queue::assertPushed(ProcessTextScan::class);
    }

    public function test_a_photo_is_required(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create())
            ->post(route('scans.store'), [
                'school_type' => 'BHS',
                'department' => 'Chemieingenieurwesen',
                'text_type' => 'Technical Report',
            ])
            ->assertSessionHasErrors(['file' => 'Bitte fotografiere deinen Text oder lade eine Datei hoch.']);

        $this->assertDatabaseCount('scans', 0);
        Queue::assertNothingPushed();
    }

    public function test_the_text_type_must_match_the_school_type(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('scans.store'), [
                'school_type' => 'AHS',
                'text_type' => 'Technical Report',
                'use_sample' => '1',
            ])
            ->assertSessionHasErrors('text_type');
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: ScanSource}>
     */
    public static function documentProvider(): array
    {
        return [
            'typed PDF' => ['essay.pdf', 'pdf', ScanSource::Pdf],
            'Word document' => ['essay.docx', 'docx', ScanSource::Word],
            'text file' => ['essay.txt', 'txt', ScanSource::Text],
        ];
    }

    #[DataProvider('documentProvider')]
    public function test_documents_can_be_uploaded(string $name, string $format, ScanSource $source): void
    {
        Queue::fake();
        Storage::fake('local');
        $user = User::factory()->create();

        $contents = match ($format) {
            'pdf' => DocumentFixtures::pdf(['Nowadays almost every student have a smartphone.']),
            'docx' => DocumentFixtures::docx(['Nowadays almost every student have a smartphone.']),
            default => "Nowadays almost every student have a smartphone.\n",
        };

        $this->actingAs($user)->post(route('scans.store'), [
            'school_type' => 'BHS',
            'department' => 'Chemieingenieurwesen',
            'text_type' => 'Essay',
            'file' => UploadedFile::fake()->createWithContent($name, $contents),
        ])->assertRedirect();

        $scan = Scan::sole();
        $this->assertSame($source, $scan->source);
        $this->assertSame($name, $scan->original_name);
        Storage::disk('local')->assertExists($scan->file_path);
        Queue::assertPushed(ProcessTextScan::class);
    }

    public function test_unsupported_formats_are_rejected_by_content(): void
    {
        $user = User::factory()->create();
        $base = ['school_type' => 'BHS', 'department' => 'Chemieingenieurwesen', 'text_type' => 'Email'];

        // An executable renamed to .pdf is still not a PDF. A real file is used,
        // because fake uploads derive their MIME type from the file name.
        $path = tempnam(sys_get_temp_dir(), 'exe');
        file_put_contents($path, "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xFF\xFF".str_repeat("\x00", 200).'This program cannot be run in DOS mode.');

        $this->actingAs($user)
            ->post(route('scans.store'), $base + ['file' => new UploadedFile($path, 'essay.pdf', null, null, true)])
            ->assertSessionHasErrors(['file' => 'Erlaubt sind PDF, Fotos (JPG, PNG), Word (.docx) und TXT.']);

        $this->actingAs($user)
            ->post(route('scans.store'), $base + ['file' => UploadedFile::fake()->create('slides.pptx', 100, 'application/vnd.openxmlformats-officedocument.presentationml.presentation')])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('scans', 0);
    }

    public function test_files_are_limited_to_ten_megabytes(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('scans.store'), [
                'school_type' => 'BHS',
                'department' => 'Chemieingenieurwesen',
                'text_type' => 'Email',
                'file' => UploadedFile::fake()->image('big.jpg')->size(10241),
            ])
            ->assertSessionHasErrors(['file' => 'Die Datei darf höchstens 10 MB groß sein.']);
    }

    public function test_setup_form_offers_photo_and_document_upload(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('scans.create'))
            ->assertOk()
            ->assertSee('Foto aufnehmen')
            ->assertSee('Datei hochladen oder hierher ziehen')
            ->assertSee('accept=".jpg,.jpeg,.png,.pdf,.docx,.txt,application/pdf', false)
            ->assertSee('PDF, JPG, PNG, Word (.docx) oder TXT')
            ->assertSee('Demo-Modus:');
    }

    public function test_heic_photos_are_only_offered_with_google_vision(): void
    {
        config(['lingolens.ocr_provider' => 'google']);

        $this->actingAs(User::factory()->create())
            ->get(route('scans.create'))
            ->assertSee('accept=".jpg,.jpeg,.png,.heic,.heif,.pdf,.docx,.txt,application/pdf', false)
            ->assertSee('PDF, JPG, PNG, HEIC, Word (.docx) oder TXT');
    }
}
