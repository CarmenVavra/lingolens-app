<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The upload form sends via fetch() (photos kept in memory, because iOS Safari
 * otherwise posted an empty body after a camera photo). The server answers
 * fetch requests with JSON errors and a followable redirect.
 */
class UploadViaFetchTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private const FETCH_HEADERS = ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];

    public function test_a_successful_upload_redirects_to_the_processing_screen(): void
    {
        Queue::fake();
        Storage::fake('local');

        $response = $this->actingAs(User::factory()->create())->post(route('scans.store'), [
            'school_type' => 'BHS',
            'department' => 'Maschinenbau',
            'text_type' => 'Blog Comment',
            'task_text' => 'Write a blog comment.',
            'file' => UploadedFile::fake()->image('IMG_0001.jpg'),
            'task_file' => UploadedFile::fake()->image('IMG_0002.jpg'),
        ], self::FETCH_HEADERS);

        $response->assertRedirect(route('scans.processing', Scan::sole()));
    }

    public function test_validation_errors_come_back_as_json_in_german(): void
    {
        $this->actingAs(User::factory()->create())->post(route('scans.store'), [
            'school_type' => 'BHS',
            'department' => 'Maschinenbau',
            'text_type' => 'Blog Comment',
        ], self::FETCH_HEADERS)
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'Bitte fotografiere deinen Text oder lade eine Datei hoch.');
    }

    public function test_the_form_has_a_place_for_errors_from_sending(): void
    {
        $this->actingAs(User::factory()->create())->get(route('scans.create'))
            ->assertOk()
            ->assertSee('x-ref="serverErrors"', false)
            ->assertSee('Das hat nicht geklappt:');
    }
}
