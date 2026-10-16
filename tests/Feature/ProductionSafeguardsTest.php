<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Jobs\ProcessTextScan;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cost protection and shared-hosting behaviour for the live server.
 */
class ProductionSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_UPLOAD = [
        'school_type' => 'BHS',
        'department' => 'Chemieingenieurwesen',
        'text_type' => 'Technical Report',
        'use_sample' => '1',
    ];

    public function test_students_are_limited_per_day(): void
    {
        Queue::fake();
        config(['lingolens.daily_scan_limit' => 2]);
        $user = User::factory()->create();
        Scan::factory()->for($user)->count(2)->create();
        Scan::factory()->for($user)->create(['created_at' => now()->subDay()]);

        $this->actingAs($user)
            ->get(route('scans.create'))
            ->assertSee('Heute noch 0 Texte möglich.');

        $this->post(route('scans.store'), self::SAMPLE_UPLOAD)
            ->assertSessionHasErrors(['file' => 'Du hast dein Tageslimit von 2 Texten erreicht. Morgen geht es weiter!']);

        $this->assertSame(3, Scan::count());
        Queue::assertNothingPushed();
    }

    public function test_a_limit_of_zero_means_unlimited(): void
    {
        Queue::fake();
        config(['lingolens.daily_scan_limit' => 0]);
        $user = User::factory()->create();
        Scan::factory()->for($user)->count(30)->create();

        $this->actingAs($user)->post(route('scans.store'), self::SAMPLE_UPLOAD)->assertSessionHasNoErrors();

        $this->assertNull($user->remainingScansToday());
    }

    public function test_registration_can_require_an_access_code(): void
    {
        config(['lingolens.registration_code' => '5CHC-2027']);
        $data = [
            'name' => 'Lena Gruber',
            'email' => 'lena@example.com',
            'password' => 'geheim-passwort',
            'password_confirmation' => 'geheim-passwort',
            'privacy' => '1',
            'school_type' => 'AHS',
        ];

        $this->get(route('register'))->assertSee('Zugangscode deiner Klasse');

        $this->post(route('register'), $data + ['access_code' => 'falsch'])
            ->assertSessionHasErrors(['access_code' => 'Der Zugangscode ist nicht korrekt.']);
        $this->assertGuest();

        $this->post(route('register'), $data + ['access_code' => '5CHC-2027'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_registration_is_open_without_access_code(): void
    {
        $this->get(route('register'))->assertDontSee('Zugangscode deiner Klasse');
    }

    public function test_analysis_can_run_right_after_the_response(): void
    {
        Bus::fake();
        config(['lingolens.process_after_response' => true]);

        $this->actingAs(User::factory()->create())->post(route('scans.store'), self::SAMPLE_UPLOAD);

        Bus::assertDispatchedAfterResponse(ProcessTextScan::class);
    }

    public function test_stuck_scans_are_marked_as_failed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('scans/1/stuck.pdf', '%PDF-1.4');

        $stuck = Scan::factory()->create(['file_path' => 'scans/1/stuck.pdf']);
        $stuck->forceFill(['updated_at' => now()->subMinutes(20)])->saveQuietly();
        $running = Scan::factory()->create();
        $done = Scan::factory()->completed()->create();
        $done->forceFill(['updated_at' => now()->subDay()])->saveQuietly();

        $this->artisan('scans:fail-stale')->assertSuccessful();

        $this->assertSame(ScanStatus::Failed, $stuck->refresh()->status);
        $this->assertStringContainsString('zu lange gedauert', $stuck->failure_reason);
        Storage::disk('local')->assertMissing('scans/1/stuck.pdf');
        $this->assertSame(ScanStatus::Processing, $running->refresh()->status);
        $this->assertSame(ScanStatus::Completed, $done->refresh()->status);
    }

    public function test_a_job_for_a_scan_that_was_given_up_does_nothing(): void
    {
        $scan = Scan::factory()->failed()->create(['uses_sample' => true]);

        ProcessTextScan::dispatchSync($scan);

        $this->assertSame(ScanStatus::Failed, $scan->refresh()->status);
        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_the_scheduler_runs_the_queue_and_the_cleanup(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command)->implode("\n");

        $this->assertStringContainsString('queue:work --stop-when-empty', $commands);
        $this->assertStringContainsString('scans:fail-stale', $commands);
    }

    public function test_install_command_prepares_the_app(): void
    {
        $this->artisan('lingolens:install')
            ->expectsOutputToContain('LingoLens AI ist bereit.')
            ->assertSuccessful();
    }
}
