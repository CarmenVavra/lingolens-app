<?php

namespace Tests\Feature;

use App\Models\Correction;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_mode_shows_marked_text_and_feedback(): void
    {
        $user = User::factory()->create();
        $scan = $this->completedScan($user);

        $this->actingAs($user)
            ->get(route('scans.show', $scan))
            ->assertOk()
            ->assertSee("Scan #{$scan->id} · Korrektur-Modus")
            ->assertSee('1  Introduction')
            ->assertSee('class="mark is-grammar"', false)
            ->assertSee('class="mark is-technical"', false)
            ->assertSee('the chemical reaction makes heat</span>.', false)
            ->assertSee('Matura-Tipp')
            ->assertSee('Textsorten-Check · Technical Report')
            ->assertSee('KI-Zusammenfassung')
            ->assertSee('Stellen zum Verbessern')
            ->assertSee('/ 11 verstanden', false);
    }

    public function test_student_text_is_escaped(): void
    {
        $user = User::factory()->create();
        $scan = $this->completedScan($user);
        $scan->update(['raw_extracted_text' => '<script>alert(1)</script> we describes it.']);

        $this->actingAs($user)
            ->get(route('scans.show', $scan))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_a_type_filter_preselects_the_first_correction_of_that_type(): void
    {
        $user = User::factory()->create();
        $scan = $this->completedScan($user);
        $firstTechnical = Correction::where('error_type', 'Technical Term')->orderBy('start_offset')->first();

        $response = $this->actingAs($user)->get(route('scans.show', ['scan' => $scan, 'type' => 'Technical Term']));

        $response->assertOk();
        $this->assertSame($firstTechnical->id, $response->viewData('correctionMode')['initial']['selected']);
        $this->assertSame(['Grammar', 'Vocabulary', 'Style'], $response->viewData('correctionMode')['initial']['hidden']);
    }

    public function test_an_invalid_type_filter_is_rejected(): void
    {
        $user = User::factory()->create();
        $scan = $this->completedScan($user);

        $this->actingAs($user)
            ->get(route('scans.show', ['scan' => $scan, 'type' => 'Spelling']))
            ->assertSessionHasErrors('type');
    }

    public function test_students_cannot_open_scans_of_others(): void
    {
        $scan = $this->completedScan(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('scans.show', $scan))
            ->assertForbidden();

        $this->getJson(route('scans.status', $scan))->assertForbidden();
        $this->get(route('scans.processing', $scan))->assertForbidden();
    }

    public function test_unfinished_scans_redirect_to_the_processing_screen(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('scans.show', $scan))
            ->assertRedirect(route('scans.processing', $scan));

        $this->get(route('scans.processing', $scan))
            ->assertOk()
            ->assertSee('Handschrift erkennen')
            ->assertSee('KI-Handschrift-Erkennung · OpenAI');
    }

    public function test_failed_scans_show_the_error_state(): void
    {
        $user = User::factory()->create();
        $scan = Scan::factory()->for($user)->failed()->create();

        $this->actingAs($user)
            ->get(route('scans.processing', $scan))
            ->assertOk()
            ->assertSee('Text konnte nicht gelesen werden')
            ->assertSee('Neues Foto aufnehmen');

        $this->getJson(route('scans.status', $scan))->assertJson(['status' => 'failed', 'redirect_url' => null]);
    }

    public function test_corrections_can_be_marked_as_understood(): void
    {
        $user = User::factory()->create();
        $this->completedScan($user);
        $correction = Correction::first();

        $this->actingAs($user)
            ->patchJson(route('corrections.update', $correction), ['understood' => true])
            ->assertOk()
            ->assertJson(['id' => $correction->id, 'understood' => true]);

        $this->assertNotNull($correction->refresh()->understood_at);

        $this->patchJson(route('corrections.update', $correction), ['understood' => false])
            ->assertJson(['understood' => false]);

        $this->assertNull($correction->refresh()->understood_at);
    }

    public function test_students_cannot_update_corrections_of_others(): void
    {
        $this->completedScan(User::factory()->create());
        $correction = Correction::first();

        $this->actingAs(User::factory()->create())
            ->patchJson(route('corrections.update', $correction), ['understood' => true])
            ->assertForbidden();

        $this->assertNull($correction->refresh()->understood_at);
    }

    public function test_the_understood_flag_is_validated(): void
    {
        $user = User::factory()->create();
        $this->completedScan($user);

        $this->actingAs($user)
            ->patchJson(route('corrections.update', Correction::first()), ['understood' => 'vielleicht'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('understood');
    }
}
