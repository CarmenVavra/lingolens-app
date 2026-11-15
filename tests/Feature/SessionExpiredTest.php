<?php

namespace Tests\Feature;

use App\Http\Responses\SessionExpiredResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * "Page Expired" (CSRF token mismatch) must never throw away what the student typed.
 */
class SessionExpiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF checks are skipped in tests, so simulate the mismatch directly.
        Route::post('/_test/token-mismatch', fn () => throw new TokenMismatchException('CSRF token mismatch.'))->middleware('web');
    }

    public function test_the_upload_form_can_fetch_a_fresh_token_before_submitting(): void
    {
        $this->get(route('csrf-token'))->assertRedirect(route('login'));

        $response = $this->actingAs(User::factory()->create())->getJson(route('csrf-token'));

        $response->assertOk()->assertJson(['token' => session()->token()]);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->get(route('scans.create'))->assertSee('csrf-token', false);
    }

    public function test_an_expired_form_goes_back_with_the_typed_input_instead_of_page_expired(): void
    {
        Log::spy();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('scans.create'))->post('/_test/token-mismatch', [
            'school_type' => 'BHS',
            'department' => 'Maschinenbau',
            'text_type' => 'Blog Comment',
            'task_text' => 'Write a blog comment about school uniforms.',
            'file' => UploadedFile::fake()->image('text.jpg'),
        ]);

        $response->assertRedirect(route('scans.create'))
            ->assertSessionHasErrors(['session' => SessionExpiredResponse::MESSAGE])
            ->assertSessionHasInput('text_type', 'Blog Comment')
            ->assertSessionHasInput('task_text', 'Write a blog comment about school uniforms.')
            ->assertSessionMissing('_old_input.file');

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $message === 'CSRF token mismatch (Page Expired)'
            && $context['authenticated'] === true
            && $context['files'] === 1
            && ! array_key_exists('task_text', $context));

        $this->get(route('scans.create'))
            ->assertOk()
            ->assertSee('Deine Eingaben sind wieder da')
            ->assertSee('Write a blog comment about school uniforms.')
            ->assertDontSee('Bitte überprüfe deine Angaben:');
    }

    public function test_the_login_page_shows_the_hint_but_never_restores_the_password(): void
    {
        $this->from(route('login'))->post('/_test/token-mismatch', ['email' => 'lena@example.test', 'password' => 'secret-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasInput('email', 'lena@example.test')
            ->assertSessionMissing('_old_input.password');

        $this->get(route('login'))->assertSee('Deine Eingaben sind wieder da');
    }

    public function test_json_requests_keep_the_419_status(): void
    {
        $this->postJson('/_test/token-mismatch')->assertStatus(419);
    }
}
