<?php

namespace Tests\Feature;

use App\Jobs\ProcessTextScan;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function configureOperator(): void
    {
        config([
            'legal.operator.name' => 'Maria Muster',
            'legal.operator.street' => 'Musterstraße 1',
            'legal.operator.city' => '1010 Wien',
            'legal.operator.email' => 'kontakt@example.at',
        ]);
    }

    public function test_imprint_is_public_and_shows_the_operator(): void
    {
        $this->configureOperator();

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Impressum')
            ->assertSee('§ 25 Mediengesetz')
            ->assertSee('Maria Muster')
            ->assertSee('Musterstraße 1')
            ->assertSee('mailto:kontakt@example.at', false)
            ->assertSee('Lernplattform zur Vorbereitung auf die schriftliche Englisch-Matura')
            ->assertDontSee('in .env eintragen')
            ->assertDontSee('UID-Nummer');
    }

    public function test_business_details_are_only_shown_when_configured(): void
    {
        $this->configureOperator();
        config(['legal.business.vat_id' => 'ATU12345678']);

        $this->get(route('legal.imprint'))->assertSee('UID-Nummer')->assertSee('ATU12345678');
    }

    public function test_missing_operator_data_is_highlighted(): void
    {
        config(['legal.operator.name' => null, 'legal.operator.email' => null]);

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('[LEGAL_NAME in .env eintragen]')
            ->assertSee('[LEGAL_EMAIL in .env eintragen]');
    }

    public function test_privacy_policy_names_processors_retention_and_rights(): void
    {
        $this->configureOperator();

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Datenschutzerklärung')
            ->assertSee('Maria Muster')
            ->assertSee('netcup GmbH')
            ->assertSee('Google Cloud EMEA Limited')
            ->assertSee('ausschließlich in der Europäischen Union')
            ->assertSee('OpenAI Ireland Ltd.')
            ->assertSee('Standardvertragsklauseln')
            ->assertSee('sofort nach der Analyse gelöscht')
            ->assertSee('§ 165 Abs. 3 TKG 2021')
            ->assertSee('Datenschutzbehörde')
            ->assertSee('Art. 15 DSGVO');
    }

    public function test_privacy_policy_mentions_non_eu_processing_without_the_eu_endpoint(): void
    {
        config(['services.google.vision_endpoint' => 'https://vision.googleapis.com']);

        $this->get(route('legal.privacy'))
            ->assertDontSee('ausschließlich in der Europäischen Union')
            ->assertSee('Auch Google kann Daten außerhalb der EU verarbeiten');
    }

    public function test_every_layout_links_to_the_legal_pages(): void
    {
        $imprint = route('legal.imprint');
        $privacy = route('legal.privacy');

        $this->get(route('login'))->assertSee($imprint, false)->assertSee($privacy, false);
        $this->get(route('register'))->assertSee($imprint, false)->assertSee($privacy, false);
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))->assertSee($imprint, false)->assertSee($privacy, false);
    }

    public function test_pages_load_no_third_party_fonts(): void
    {
        $this->get(route('login'))->assertDontSee('fonts.googleapis.com');
        $this->get(route('legal.privacy'))->assertDontSee('fonts.googleapis.com');
    }

    public function test_registration_requires_and_stores_privacy_consent(): void
    {
        $data = [
            'name' => 'Lena Gruber',
            'email' => 'lena@example.com',
            'password' => 'geheim-passwort',
            'password_confirmation' => 'geheim-passwort',
            'school_type' => 'AHS',
        ];

        $this->get(route('register'))->assertSee('Datenschutzerklärung</a>', false);

        $this->post(route('register'), $data)
            ->assertSessionHasErrors(['privacy' => 'Bitte bestätige, dass du die Datenschutzerklärung gelesen hast.']);
        $this->assertGuest();

        $this->post(route('register'), $data + ['privacy' => '1'])->assertRedirect(route('dashboard'));
        $this->assertNotNull(User::firstWhere('email', 'lena@example.com')->privacy_accepted_at);
    }

    public function test_google_vision_uses_the_eu_endpoint_by_default(): void
    {
        config(['services.google.vision_key' => 'k', 'services.openai.key' => 'k']);
        Storage::fake('local');
        Http::fake(['*' => Http::response(['responses' => [['fullTextAnnotation' => ['text' => '']]]])]);

        Storage::disk('local')->put('scans/1/photo.jpg', 'image');
        $scan = Scan::factory()->create(['uses_sample' => false, 'file_path' => 'scans/1/photo.jpg']);

        ProcessTextScan::dispatchSync($scan);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://eu-vision.googleapis.com/v1/images:annotate'));
    }
}
